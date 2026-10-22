<?php
/**
 * Registers the virtual media folder taxonomy.
 *
 * @package Unstoppable_Media_Folders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMFD_Taxonomy
 *
 * Creates a hierarchical taxonomy attached to the `attachment` post type.
 * This taxonomy IS the folder structure. Folders are virtual: assigning a
 * media item to a folder only creates a term relationship in the database,
 * it never moves the underlying file, so URLs never change.
 */
class UMFD_Taxonomy {

	/**
	 * Hook registration.
	 */
	public function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the taxonomy.
	 *
	 * Static so it can also be called directly on activation.
	 */
	public static function register() {
		$labels = array(
			'name'              => _x( 'Media Folders', 'taxonomy general name', 'unstoppable-media-folders' ),
			'singular_name'     => _x( 'Media Folder', 'taxonomy singular name', 'unstoppable-media-folders' ),
			'search_items'      => __( 'Search Folders', 'unstoppable-media-folders' ),
			'all_items'         => __( 'All Folders', 'unstoppable-media-folders' ),
			'parent_item'       => __( 'Parent Folder', 'unstoppable-media-folders' ),
			'parent_item_colon' => __( 'Parent Folder:', 'unstoppable-media-folders' ),
			'edit_item'         => __( 'Edit Folder', 'unstoppable-media-folders' ),
			'update_item'       => __( 'Update Folder', 'unstoppable-media-folders' ),
			'add_new_item'      => __( 'Add New Folder', 'unstoppable-media-folders' ),
			'new_item_name'     => __( 'New Folder Name', 'unstoppable-media-folders' ),
			'menu_name'         => __( 'Media Folders', 'unstoppable-media-folders' ),
			'not_found'         => __( 'No folders found.', 'unstoppable-media-folders' ),
		);

		$args = array(
			'labels'             => $labels,
			'hierarchical'       => true,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_tagcloud'      => false,
			'show_admin_column'  => true,
			'show_in_quick_edit' => true,
			// Expose to REST so the block editor and modal stay compatible.
			'show_in_rest'       => true,
			'rest_base'          => 'umfd-media-folders',
			'query_var'          => false,
			'rewrite'            => false,
			// Attachments use the 'inherit' status, so the default post term
			// counter reports 0. This generic counter tallies every related
			// object regardless of status, giving correct folder counts.
			'update_count_callback' => '_update_generic_term_count',
			// Folder management is gated on the standard media capability.
			'capabilities'       => array(
				'manage_terms' => 'upload_files',
				'edit_terms'   => 'upload_files',
				'delete_terms' => 'upload_files',
				'assign_terms' => 'upload_files',
			),
		);

		register_taxonomy( UMFD_TAXONOMY, 'attachment', $args );
	}
}
