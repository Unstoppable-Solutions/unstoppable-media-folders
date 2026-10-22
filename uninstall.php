<?php
/**
 * Uninstall routine for Unstoppable Media Folders.
 *
 * Runs only when the plugin is deleted from the WordPress admin. Media files
 * and attachments are NEVER touched. This only removes the virtual folder
 * structure (taxonomy terms) and any plugin options.
 *
 * @package Unstoppable_Media_Folders
 */

// Exit if accessed directly or not during an uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all folder terms for a single site.
 */
function umfd_uninstall_site() {
	$taxonomy = 'umfd_media_folder';

	// The taxonomy may not be registered during uninstall, so query terms directly.
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( (int) $term_id, $taxonomy );
		}
	}
}

if ( is_multisite() ) {
	$umfd_site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $umfd_site_ids as $umfd_site_id ) {
		switch_to_blog( $umfd_site_id );
		umfd_uninstall_site();
		restore_current_blog();
	}
} else {
	umfd_uninstall_site();
}
