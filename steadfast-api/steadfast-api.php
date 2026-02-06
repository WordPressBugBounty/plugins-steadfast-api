<?php

/**
 * Plugin Name: SteadFast API
 * Description: Send to SteadFast gives you the ability to send your parcel request to SteadFast directly from your WooCommerce dashboard, it enables booking automation from your WordPress website. You can send your parcel to SteadFast one by one, or you can choose bulk send from "bulk action" dropdown.
 * Version: 1.0.4
 * Author: SteadFast Courier LTD
 * Text Domain: steadfast-api
 * Author URI: https://steadfast.com.bd/
 * Requires Plugins: woocommerce
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;
defined( 'STDF_PLUGIN_URL' ) || define( 'STDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'STDF_PLUGIN_DIR' ) || define( 'STDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
defined( 'STDF_PLUGIN_FILE' ) || define( 'STDF_PLUGIN_FILE', plugin_basename( __FILE__ ) );
defined( 'STDF_PLUGIN_VERSION' ) || define( 'STDF_PLUGIN_VERSION', '1.0.4' );



/**
 * Plugin uninstall hooks.
 *
 * @return void
 */
function stdf_plugin_uninstall_hooks() {
	delete_option( 'api_settings_tab_api_secret_key' );
	delete_option( 'api_settings_tab_api_key' );
	delete_option( 'stdf_settings_tab_checkbox' );
	delete_option( 'stdf_settings_tab_notes' );
}

register_uninstall_hook( STDF_PLUGIN_FILE, 'stdf_plugin_uninstall_hooks' );

if ( ! class_exists( 'STDF_Courier_Main' ) ) {
	/**
	 * Class STDF_Courier_Main
	 */
	class STDF_Courier_Main {

		protected static $_instance = null;

		function __construct() {
			$this->includes_files();
			$this->define_scripts();
		}

		/**
		 * @return void
		 */
		function includes_files() {
			require_once STDF_PLUGIN_DIR . '/includes/class-hooks.php';
			require_once STDF_PLUGIN_DIR . '/includes/functions.php';
			require_once STDF_PLUGIN_DIR . '/includes/class-admin-menu.php';
			require_once STDF_PLUGIN_DIR . '/includes/ajax.php';
		}

		/**
		 * Admin scripts.
		 *
		 * @return void
		 */
		function admin_script() {
		
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			if ($page == 'steadfast') {
				wp_enqueue_style('stdf-style-dashboard', STDF_PLUGIN_URL . 'assets/admin/css/dashboard.css', array(), STDF_PLUGIN_VERSION, 'all');
				wp_enqueue_script('stdf-script-dashboard', STDF_PLUGIN_URL . 'assets/admin/js/dashboard.js', array(), STDF_PLUGIN_VERSION);
			}

			wp_enqueue_script( 'stdf-jquery', plugins_url( '/assets/admin/js/scripts.js', __FILE__ ), array( 'jquery' ), STDF_PLUGIN_VERSION, true );
			wp_enqueue_style( 'stdf-style-main', STDF_PLUGIN_URL . 'assets/admin/css/style.css', array(), STDF_PLUGIN_VERSION, 'all' );
			wp_localize_script( 'ajax-script', 'stdf-api', $this->localize_scripts() );
		}

		function stdf_enqueue_frontend_styles() {
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			if ( $page == 'stdf-invoice' && ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'stdf_print_order_nonce' ) ) ) {
				wp_enqueue_style( 'stdf-invoice-styles', plugin_dir_url( __FILE__ ) . 'assets/invoice/css/style.css', array(), STDF_PLUGIN_VERSION );
			}
		}

		function define_scripts() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'stdf_enqueue_frontend_styles' ) );
		}

		/**
		 * @return mixed|null
		 */
		function localize_scripts() {
			return apply_filters( 'stdf_api_filters_localize_scripts', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			) );
		}

		/**
		 * @return STDF_Courier_Main
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

	}

	STDF_Courier_Main::instance();
}
