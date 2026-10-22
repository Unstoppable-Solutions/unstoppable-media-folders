<?php
/**
 * AJAX handlers for folder management.
 *
 * @package Unstoppable_Media_Folders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMFD_Ajax
 *
 * All handlers verify a nonce and the `upload_files` capability before doing
 * any work. Input is unslashed then sanitized; output is structured JSON.
 */
class UMFD_Ajax {

	/**
	 * The AJAX nonce action/key shared with the front-end script.
	 */
	const NONCE_ACTION = 'umfd_ajax_nonce';

	/**
	 * Register AJAX endpoints.
	 */
	public function init() {
		add_action( 'wp_ajax_umfd_get_tree', array( $this, 'get_tree' ) );
		add_action( 'wp_ajax_umfd_create_folder', array( $this, 'create_folder' ) );
		add_action( 'wp_ajax_umfd_rename_folder', array( $this, 'rename_folder' ) );
		add_action( 'wp_ajax_umfd_delete_folder', array( $this, 'delete_folder' ) );
		add_action( 'wp_ajax_umfd_assign_media', array( $this, 'assign_media' ) );

		// Native grid / modal filtering (block editor compatible).
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_grid_query' ) );
	}

	/**
	 * Verify nonce + capability for every state-changing request.
	 *
	 * Sends a JSON error and stops execution if either check fails.
	 */
	private function verify_request() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed. Please reload the page and try again.', 'unstoppable-media-folders' ) ),
				403
			);
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to manage media folders.', 'unstoppable-media-folders' ) ),
				403
			);
		}
	}

	/**
	 * Return the full folder tree plus item counts.
	 */
	public function get_tree() {
		$this->verify_request();

		// Recount terms so folder counts are accurate for attachments.
		$this->refresh_counts();

		wp_send_json_success(
			array(
				'folders'           => $this->get_folder_list(),
				'all_count'         => $this->count_all_media(),
				'uncategorized'     => $this->count_uncategorized_media(),
			)
		);
	}

	/**
	 * Recalculate folder term counts (attachments use the 'inherit' status,
	 * which the default counter ignores).
	 */
	private function refresh_counts() {
		$ids = get_terms(
			array(
				'taxonomy'   => UMFD_TAXONOMY,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( ! is_wp_error( $ids ) && ! empty( $ids ) ) {
			wp_update_term_count_now( $ids, UMFD_TAXONOMY );
		}
	}

	/**
	 * Create a new folder (optionally nested under a parent).
	 */
	public function create_folder() {
		$this->verify_request();

		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().
		$parent = isset( $_POST['parent'] ) ? absint( wp_unslash( $_POST['parent'] ) ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().

		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a folder name.', 'unstoppable-media-folders' ) ) );
		}

		if ( $parent > 0 && ! term_exists( $parent, UMFD_TAXONOMY ) ) {
			wp_send_json_error( array( 'message' => __( 'The selected parent folder no longer exists.', 'unstoppable-media-folders' ) ) );
		}

		$result = wp_insert_term( $name, UMFD_TAXONOMY, array( 'parent' => $parent ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$term = get_term( $result['term_id'], UMFD_TAXONOMY );

		wp_send_json_success(
			array(
				'message' => __( 'Folder created.', 'unstoppable-media-folders' ),
				'folder'  => $this->format_term( $term ),
			)
		);
	}

	/**
	 * Rename an existing folder.
	 */
	public function rename_folder() {
		$this->verify_request();

		$term_id = isset( $_POST['folder_id'] ) ? absint( wp_unslash( $_POST['folder_id'] ) ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().
		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().

		if ( $term_id <= 0 || ! term_exists( $term_id, UMFD_TAXONOMY ) ) {
			wp_send_json_error( array( 'message' => __( 'That folder no longer exists.', 'unstoppable-media-folders' ) ) );
		}

		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a folder name.', 'unstoppable-media-folders' ) ) );
		}

		$result = wp_update_term( $term_id, UMFD_TAXONOMY, array( 'name' => $name ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$term = get_term( $term_id, UMFD_TAXONOMY );

		wp_send_json_success(
			array(
				'message' => __( 'Folder renamed.', 'unstoppable-media-folders' ),
				'folder'  => $this->format_term( $term ),
			)
		);
	}

	/**
	 * Delete a folder.
	 *
	 * Media files are NEVER deleted. WordPress simply drops the term and its
	 * relationships, so affected media falls back to "Uncategorized".
	 * Child folders are re-parented to the deleted folder's parent by core.
	 */
	public function delete_folder() {
		$this->verify_request();

		$term_id = isset( $_POST['folder_id'] ) ? absint( wp_unslash( $_POST['folder_id'] ) ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().

		if ( $term_id <= 0 || ! term_exists( $term_id, UMFD_TAXONOMY ) ) {
			wp_send_json_error( array( 'message' => __( 'That folder no longer exists.', 'unstoppable-media-folders' ) ) );
		}

		$result = wp_delete_term( $term_id, UMFD_TAXONOMY );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'The folder could not be deleted.', 'unstoppable-media-folders' ) ) );
		}

		wp_send_json_success(
			array(
				'message'   => __( 'Folder deleted. Its media was moved to Uncategorized.', 'unstoppable-media-folders' ),
				'folder_id' => $term_id,
			)
		);
	}

	/**
	 * Assign one or more media items to a folder.
	 *
	 * Modes:
	 *  - "add"    : append the folder to each item (keeps existing folders).
	 *  - "set"    : replace each item's folders with this one.
	 *  - "remove" : remove ALL folders from each item (move to Uncategorized).
	 */
	public function assign_media() {
		$this->verify_request();

		$mode        = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'add';  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().
		$folder_id   = isset( $_POST['folder_id'] ) ? absint( wp_unslash( $_POST['folder_id'] ) ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().
		$from_folder = isset( $_POST['from_folder'] ) ? absint( wp_unslash( $_POST['from_folder'] ) ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().

		$attachment_ids = array();
		if ( isset( $_POST['attachment_ids'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in verify_request().
			$raw            = wp_unslash( $_POST['attachment_ids'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- IDs cast via absint below; nonce checked in verify_request().
			$raw            = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
			$attachment_ids = array_filter( array_map( 'absint', $raw ) );
		}

		if ( empty( $attachment_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No media items were selected.', 'unstoppable-media-folders' ) ) );
		}

		$allowed_modes = array( 'add', 'set', 'move', 'remove' );
		if ( ! in_array( $mode, $allowed_modes, true ) ) {
			$mode = 'add';
		}

		if ( 'remove' !== $mode && ( $folder_id <= 0 || ! term_exists( $folder_id, UMFD_TAXONOMY ) ) ) {
			wp_send_json_error( array( 'message' => __( 'That folder no longer exists.', 'unstoppable-media-folders' ) ) );
		}

		$updated = 0;
		foreach ( $attachment_ids as $attachment_id ) {
			// Only touch real attachments the user can edit.
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}
			if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
				continue;
			}

			if ( 'remove' === $mode ) {
				wp_set_object_terms( $attachment_id, array(), UMFD_TAXONOMY, false );
			} elseif ( 'set' === $mode ) {
				wp_set_object_terms( $attachment_id, array( $folder_id ), UMFD_TAXONOMY, false );
			} elseif ( 'move' === $mode ) {
				// Move between folders: drop the source folder (if any), keep the
				// rest, then add the target folder.
				if ( $from_folder > 0 ) {
					wp_remove_object_terms( $attachment_id, array( $from_folder ), UMFD_TAXONOMY );
				}
				wp_set_object_terms( $attachment_id, array( $folder_id ), UMFD_TAXONOMY, true );
			} else { // add.
				wp_set_object_terms( $attachment_id, array( $folder_id ), UMFD_TAXONOMY, true );
			}
			$updated++;
		}

		wp_send_json_success(
			array(
				/* translators: %d: number of media items updated. */
				'message' => sprintf( _n( '%d item moved.', '%d items moved.', $updated, 'unstoppable-media-folders' ), $updated ),
				'updated' => $updated,
			)
		);
	}

	/**
	 * Inject a folder filter into the native media grid / modal query.
	 *
	 * The `query-attachments` core action verifies its own nonce and edit
	 * capability before this filter runs, so reading the request value here is
	 * safe for a read-only filter.
	 *
	 * @param array $query Existing WP_Query args.
	 * @return array
	 */
	public function filter_grid_query( $query ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter; core verifies the query-attachments nonce.
		if ( ! isset( $_REQUEST['umfd_folder'] ) ) {
			return $query;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$folder = sanitize_text_field( wp_unslash( $_REQUEST['umfd_folder'] ) );

		if ( '' === $folder ) {
			return $query;
		}

		if ( 'uncategorized' === $folder ) {
			$query['tax_query'] = array(
				array(
					'taxonomy' => UMFD_TAXONOMY,
					'operator' => 'NOT EXISTS',
				),
			);
		} else {
			$query['tax_query'] = array(
				array(
					'taxonomy' => UMFD_TAXONOMY,
					'field'    => 'term_id',
					'terms'    => absint( $folder ),
				),
			);
		}

		return $query;
	}

	/*
	 * ---------------------------------------------------------------------
	 * Helpers
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Get every folder formatted for the front-end tree.
	 *
	 * @return array
	 */
	private function get_folder_list() {
		$terms = get_terms(
			array(
				'taxonomy'   => UMFD_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$folders = array();
		foreach ( $terms as $term ) {
			$folders[] = $this->format_term( $term );
		}

		return $folders;
	}

	/**
	 * Format a single term object for JSON output.
	 *
	 * @param WP_Term $term Term object.
	 * @return array
	 */
	private function format_term( $term ) {
		return array(
			'id'     => (int) $term->term_id,
			'name'   => $term->name,
			'parent' => (int) $term->parent,
			'count'  => (int) $term->count,
		);
	}

	/**
	 * Count all media attachments.
	 *
	 * @return int
	 */
	private function count_all_media() {
		$counts = wp_count_posts( 'attachment' );
		$total  = 0;
		foreach ( (array) $counts as $status => $num ) {
			if ( 'trash' === $status ) {
				continue;
			}
			$total += (int) $num;
		}
		return $total;
	}

	/**
	 * Count attachments that are not assigned to any folder.
	 *
	 * @return int
	 */
	private function count_uncategorized_media() {
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => false,
				'tax_query'      => array(
					array(
						'taxonomy' => UMFD_TAXONOMY,
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}
}
