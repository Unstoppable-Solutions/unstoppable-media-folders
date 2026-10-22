<?php
/**
 * Main plugin orchestrator.
 *
 * @package Unstoppable_Media_Folders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMFD_Plugin
 *
 * Boots the taxonomy, admin UI, and AJAX components.
 */
class UMFD_Plugin {

	/**
	 * Single instance of the class.
	 *
	 * @var UMFD_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Taxonomy component.
	 *
	 * @var UMFD_Taxonomy
	 */
	public $taxonomy;

	/**
	 * Admin component.
	 *
	 * @var UMFD_Admin
	 */
	public $admin;

	/**
	 * AJAX component.
	 *
	 * @var UMFD_Ajax
	 */
	public $ajax;

	/**
	 * Get the single instance.
	 *
	 * @return UMFD_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Wires up the components.
	 */
	private function __construct() {
		$this->taxonomy = new UMFD_Taxonomy();
		$this->ajax     = new UMFD_Ajax();
		$this->admin    = new UMFD_Admin();

		$this->taxonomy->init();
		$this->ajax->init();

		// Admin-only UI.
		if ( is_admin() ) {
			$this->admin->init();
		}
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {}
}
