<?php
/**
 * Admin integration: assets, attachment fields, bulk actions, filtering.
 *
 * @package Unstoppable_Media_Folders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMFD_Admin
 */
class UMFD_Admin {

	/**
	 * Hook registration.
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Folder selector on the attachment edit screen / modal.
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_folder_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_folder_field' ), 10, 2 );

		// List-table bulk actions ("Move to: <folder>").
		add_filter( 'bulk_actions-upload', array( $this, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'handle_bulk_actions' ), 10, 3 );

		// List-mode filtering by folder.
		add_action( 'pre_get_posts', array( $this, 'filter_list_query' ) );

		// File a newly uploaded item into the folder the user is viewing.
		add_action( 'add_attachment', array( $this, 'assign_uploaded_to_folder' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'bulk_action_notice' ) );
	}

	/**
	 * Assign a freshly uploaded attachment to the folder the user has selected.
	 *
	 * The admin script injects the active folder id into the uploader request as
	 * `umfd_upload_folder`. This fires on `add_attachment`, so the item is filed
	 * at creation time — no extra request and no race with the grid view.
	 *
	 * Every WordPress media-uploader request is signed with the `media-form`
	 * nonce (set in plupload's multipart params), which is verified here before
	 * anything is read or changed. The request is ignored unless the nonce is
	 * valid, the user can upload and edit the item, and the target folder exists.
	 *
	 * @param int $attachment_id The new attachment's post ID.
	 */
	public function assign_uploaded_to_folder( $attachment_id ) {
		// Verify the uploader nonce first, before reading any other request data.
		if ( ! isset( $_POST['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'media-form' ) ) {
			return;
		}

		if ( empty( $_POST['umfd_upload_folder'] ) ) {
			return;
		}

		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			return;
		}

		$folder = sanitize_text_field( wp_unslash( $_POST['umfd_upload_folder'] ) );

		// "All Media" ('') and "Uncategorized" mean "no folder" — leave it be.
		if ( '' === $folder || 'uncategorized' === $folder ) {
			return;
		}

		$folder_id = absint( $folder );
		if ( $folder_id <= 0 || ! term_exists( $folder_id, UMFD_TAXONOMY ) ) {
			return;
		}

		wp_set_object_terms( $attachment_id, array( $folder_id ), UMFD_TAXONOMY, true );
	}

	/**
	 * Enqueue CSS/JS where the media UI is present.
	 *
	 * The full sidebar only loads on the Media Library screen (upload.php).
	 * The folder dropdown filter loads everywhere the media modal is used
	 * (post editors included) so grid/modal filtering works there too.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$media_hooks = array( 'upload.php', 'post.php', 'post-new.php' );

		if ( ! in_array( $hook, $media_hooks, true ) ) {
			return;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		$is_library = ( 'upload.php' === $hook );

		// jQuery UI drag-and-drop (bundled with WordPress, never our own copy).
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );

		wp_enqueue_style(
			'umfd-admin',
			UMFD_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			UMFD_VERSION
		);

		wp_enqueue_script(
			'umfd-admin',
			UMFD_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable' ),
			UMFD_VERSION,
			true
		);

		// Current list-mode folder filter (for highlighting), read-only.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only UI state.
		$current = isset( $_GET['umfd_folder'] ) ? sanitize_text_field( wp_unslash( $_GET['umfd_folder'] ) ) : '';

		wp_localize_script(
			'umfd-admin',
			'umfdData',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( UMFD_Ajax::NONCE_ACTION ),
				'isLibrary'    => $is_library,
				'currentMode'  => $this->current_library_mode(),
				'currentFolder' => $current,
				'libraryUrl'   => admin_url( 'upload.php' ),
				'canManage'    => current_user_can( 'upload_files' ),
				'strings'      => $this->i18n_strings(),
			)
		);
	}

	/**
	 * Determine the current Media Library mode (list or grid).
	 *
	 * @return string
	 */
	private function current_library_mode() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only UI state.
		$mode = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : get_user_option( 'media_library_mode' );
		return ( 'list' === $mode ) ? 'list' : 'grid';
	}

	/**
	 * Translatable strings passed to the script.
	 *
	 * @return array
	 */
	private function i18n_strings() {
		return array(
			'foldersTitle'    => __( 'Folders', 'unstoppable-media-folders' ),
			'allMedia'        => __( 'All Media', 'unstoppable-media-folders' ),
			'uncategorized'   => __( 'Uncategorized', 'unstoppable-media-folders' ),
			'newFolder'       => __( 'New Folder', 'unstoppable-media-folders' ),
			'addFolder'       => __( 'Add Folder', 'unstoppable-media-folders' ),
			'addSubfolder'    => __( 'Add subfolder', 'unstoppable-media-folders' ),
			'rename'          => __( 'Rename', 'unstoppable-media-folders' ),
			'delete'          => __( 'Delete', 'unstoppable-media-folders' ),
			'save'            => __( 'Save', 'unstoppable-media-folders' ),
			'cancel'          => __( 'Cancel', 'unstoppable-media-folders' ),
			'promptName'      => __( 'Folder name:', 'unstoppable-media-folders' ),
			'promptRename'    => __( 'New folder name:', 'unstoppable-media-folders' ),
			'confirmDelete'   => __( 'Delete this folder? Media files will not be deleted; they will move to Uncategorized.', 'unstoppable-media-folders' ),
			'loading'         => __( 'Loading folders…', 'unstoppable-media-folders' ),
			'emptyState'      => __( 'No folders yet. Use “Add Folder” to create your first one.', 'unstoppable-media-folders' ),
			'error'           => __( 'Something went wrong. Please try again.', 'unstoppable-media-folders' ),
			'droppedToFolder' => __( 'Moved to folder.', 'unstoppable-media-folders' ),
			'noPermissionItem' => __( 'That item could not be moved (no edit permission).', 'unstoppable-media-folders' ),
			'filterLabel'     => __( 'Filter by folder', 'unstoppable-media-folders' ),
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Attachment edit screen / modal field
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Add a folder checkbox group to the attachment details panel.
	 *
	 * @param array   $form_fields Existing fields.
	 * @param WP_Post $post        Attachment.
	 * @return array
	 */
	public function add_folder_field( $form_fields, $post ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $form_fields;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => UMFD_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		$assigned = wp_get_object_terms( $post->ID, UMFD_TAXONOMY, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $assigned ) ) {
			$assigned = array();
		}

		$html = '<div class="umfd-attachment-folders">';

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$html .= '<p class="description">' . esc_html__( 'No folders yet. Create folders from the Media Library list view.', 'unstoppable-media-folders' ) . '</p>';
		} else {
			foreach ( $terms as $term ) {
				$depth   = count( get_ancestors( $term->term_id, UMFD_TAXONOMY, 'taxonomy' ) );
				$prefix  = str_repeat( '&mdash;&nbsp;', $depth );
				$checked = in_array( (int) $term->term_id, array_map( 'intval', $assigned ), true );

				$html .= sprintf(
					'<label class="umfd-folder-choice"><input type="checkbox" name="attachments[%1$d][umfd_media_folder][]" value="%2$d" %3$s /> %4$s%5$s</label>',
					(int) $post->ID,
					(int) $term->term_id,
					checked( $checked, true, false ),
					wp_kses( $prefix, array() ),
					esc_html( $term->name )
				);
			}
		}

		$html .= '</div>';

		$form_fields['umfd_media_folder'] = array(
			'label' => __( 'Media Folders', 'unstoppable-media-folders' ),
			'input' => 'html',
			'html'  => $html,
		);

		return $form_fields;
	}

	/**
	 * Save folder assignments from the attachment details panel.
	 *
	 * The core `save-attachment` AJAX action already verifies its nonce and
	 * the edit capability, so this only needs to validate the data.
	 *
	 * @param array $post       Attachment post data.
	 * @param array $attachment Submitted attachment fields.
	 * @return array
	 */
	public function save_folder_field( $post, $attachment ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $post;
		}

		if ( ! isset( $post['ID'] ) ) {
			return $post;
		}

		$attachment_id = absint( $post['ID'] );

		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return $post;
		}

		if ( isset( $attachment['umfd_media_folder'] ) && is_array( $attachment['umfd_media_folder'] ) ) {
			$folder_ids = array_filter( array_map( 'absint', $attachment['umfd_media_folder'] ) );
			wp_set_object_terms( $attachment_id, $folder_ids, UMFD_TAXONOMY, false );
		} else {
			// No boxes checked = remove from all folders.
			wp_set_object_terms( $attachment_id, array(), UMFD_TAXONOMY, false );
		}

		return $post;
	}

	/*
	 * ---------------------------------------------------------------------
	 * Bulk actions (list mode)
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Register a "Move to: <folder>" bulk action for each folder.
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array
	 */
	public function register_bulk_actions( $actions ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $actions;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => UMFD_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$depth  = count( get_ancestors( $term->term_id, UMFD_TAXONOMY, 'taxonomy' ) );
				$prefix = str_repeat( '— ', $depth );

				/* translators: %s: folder name. */
				$actions[ 'umfd_move_' . $term->term_id ] = sprintf( __( 'Move to: %s', 'unstoppable-media-folders' ), $prefix . $term->name );
			}
		}

		$actions['umfd_remove_all'] = __( 'Remove from all folders', 'unstoppable-media-folders' );

		return $actions;
	}

	/**
	 * Handle the bulk "move" actions.
	 *
	 * The bulk action nonce is verified by WordPress core before this runs.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $doaction Selected action.
	 * @param array  $ids      Selected attachment IDs.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect, $doaction, $ids ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $redirect;
		}

		$ids     = array_filter( array_map( 'absint', (array) $ids ) );
		$updated = 0;

		if ( 0 === strpos( $doaction, 'umfd_move_' ) ) {
			$term_id = absint( substr( $doaction, strlen( 'umfd_move_' ) ) );

			if ( $term_id <= 0 || ! term_exists( $term_id, UMFD_TAXONOMY ) ) {
				return $redirect;
			}

			foreach ( $ids as $id ) {
				if ( current_user_can( 'edit_post', $id ) ) {
					wp_set_object_terms( $id, array( $term_id ), UMFD_TAXONOMY, false );
					$updated++;
				}
			}
			$redirect = add_query_arg( 'umfd_moved', $updated, $redirect );

		} elseif ( 'umfd_remove_all' === $doaction ) {
			foreach ( $ids as $id ) {
				if ( current_user_can( 'edit_post', $id ) ) {
					wp_set_object_terms( $id, array(), UMFD_TAXONOMY, false );
					$updated++;
				}
			}
			$redirect = add_query_arg( 'umfd_moved', $updated, $redirect );
		}

		return $redirect;
	}

	/**
	 * Success notice after a bulk move.
	 */
	public function bulk_action_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by core redirect.
		if ( ! isset( $_GET['umfd_moved'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count = absint( wp_unslash( $_GET['umfd_moved'] ) );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of media items moved. */
					_n( '%d media item updated.', '%d media items updated.', $count, 'unstoppable-media-folders' ),
					$count
				)
			)
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * List-mode filtering
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Filter the Media Library list query by the selected folder.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function filter_list_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'upload' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation filter.
		if ( ! isset( $_GET['umfd_folder'] ) || '' === $_GET['umfd_folder'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$folder = sanitize_text_field( wp_unslash( $_GET['umfd_folder'] ) );

		if ( 'uncategorized' === $folder ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => UMFD_TAXONOMY,
						'operator' => 'NOT EXISTS',
					),
				)
			);
		} else {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => UMFD_TAXONOMY,
						'field'    => 'term_id',
						'terms'    => absint( $folder ),
					),
				)
			);
		}
	}
}
