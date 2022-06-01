<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://sourcestrike.com
 * @since      0.1.0
 *
 * @package    Source_Migrator
 * @subpackage Source_Migrator/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Source_Migrator
 * @subpackage Source_Migrator/admin
 * @author     SourceStrike <justin@sourcestrike.com>
 */
class Source_Migrator_Admin {

	private $plugin_name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		// $this->import_content_images();
		$this->helpers();
		$this->admin_ajax();

		add_action('admin_menu', array($this, 'create_admin_menu'));

	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
	}


	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'SourceMigrator', array(
			'admin_ajax' => admin_url('admin-ajax.php'),
		));
	}

	public function create_admin_menu() {
		$hook = add_submenu_page(
			'tools.php',
			'Source Migrator',
			'Source Migrator',
			'manage_options',
			'source-migrator',
			array($this, 'create_admin_page')
		);
	}

	public function helpers() {
		require plugin_dir_path( __FILE__ ) . 'partials/helpers.php';
	}

	public function admin_ajax() {
		require plugin_dir_path( __FILE__ ) . 'partials/admin-ajax.php';
	}

	public function create_admin_page() {
		require plugin_dir_path( __FILE__ ) . 'partials/display.php';
	}
}
