<?php
/**
 * Plugin Name:       Unstoppable Media Folders
 * Plugin URI:        https://unstoppablesolutions.app/plugins/unstoppable-media-folders
 * Description:       Organize the WordPress Media Library into virtual, nestable folders without moving files on disk. Existing media URLs never break.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Unstoppable Solutions
 * Author URI:        https://unstoppablesolutions.app
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unstoppable-media-folders
 * Domain Path:       /languages
 *
 * @package Unstoppable_Media_Folders
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * -------------------------------------------------------------------------
 * Constants
 * -------------------------------------------------------------------------
 */
define( 'UMFD_VERSION', '1.1.0' );
define( 'UMFD_PLUGIN_FILE', __FILE__ );
define( 'UMFD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UMFD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UMFD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The taxonomy slug used as the virtual folder structure.
 */
define( 'UMFD_TAXONOMY', 'umfd_media_folder' );

/*
 * -------------------------------------------------------------------------
 * Load required classes
 * -------------------------------------------------------------------------
 */
require_once UMFD_PLUGIN_DIR . 'includes/class-umfd-taxonomy.php';
require_once UMFD_PLUGIN_DIR . 'includes/class-umfd-ajax.php';
require_once UMFD_PLUGIN_DIR . 'includes/class-umfd-admin.php';
require_once UMFD_PLUGIN_DIR . 'includes/class-umfd-plugin.php';

/**
 * Get the main plugin instance.
 *
 * @return UMFD_Plugin
 */
function umfd_plugin() {
	return UMFD_Plugin::get_instance();
}

/*
 * -------------------------------------------------------------------------
 * Activation / Deactivation
 * -------------------------------------------------------------------------
 */

/**
 * Runs on plugin activation.
 *
 * Registers the taxonomy so the rewrite/term tables are ready, then flushes
 * rewrite rules. No data is destroyed and no files are moved.
 */
function umfd_activate() {
	UMFD_Taxonomy::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'umfd_activate' );

/**
 * Runs on plugin deactivation.
 *
 * Only flushes rewrite rules. Folders (taxonomy terms) and media stay intact.
 */
function umfd_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'umfd_deactivate' );

/*
 * -------------------------------------------------------------------------
 * Initialize the plugin
 * -------------------------------------------------------------------------
 */
add_action( 'plugins_loaded', 'umfd_plugin' );
