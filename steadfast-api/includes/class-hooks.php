<?php

defined('ABSPATH') || exit;

if (!class_exists('STDF_Hooks')) {

	class STDF_Hooks
	{

		protected static $_instance = null;

		public $success = '';

		function __construct()
		{


			$checkbox = get_option('stdf_settings_tab_checkbox', false);

			// Register Bulk send order list table. WooCommerce - 7.0.0 version
			add_filter('bulk_actions-edit-shop_order', array($this, 'register_bulk_action_send_steadfast'));
			add_action('handle_bulk_actions-edit-shop_order', array($this, 'send_to_steadfast_bulk_process'), 20, 3);

			// Register Bulk send order list table. WooCommerce - Latest version
			add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'register_bulk_action_send_steadfast'), 999);
			add_action('handle_bulk_actions-woocommerce_page_wc-orders', array($this, 'send_to_steadfast_bulk_process'), 20, 3);

			if ($checkbox == 'yes') {
				// Add custom column order list table. WooCommerce - 7.0.0 version
				add_filter('manage_edit-shop_order_columns', array($this, 'add_steadfast_custom_column'));
				add_action('manage_shop_order_posts_custom_column', array($this, 'add_custom_column_content_order_list_table'));

				// Add custom column content order list table. WooCommerce- Latest version
				add_filter('woocommerce_shop_order_list_table_columns', array($this, 'add_steadfast_custom_column'));
				add_action('woocommerce_shop_order_list_table_custom_column', array($this, 'add_custom_column_content_order_page'), 10, 2);
			}

			// List table row unlink. WooCommerce - 7.0.0 version
			add_filter('post_class', array($this, 'admin_orders_table_row_unlink'), 10, 3);
			// List table row unlink. WooCommerce - Latest version
			add_filter('woocommerce_shop_order_list_table_order_css_classes', array($this, '_admin_orders_table_row_unlink'));

			add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 4);
			add_action('init', array($this, 'stdf_invoice_template'));
			add_action('admin_menu', array($this, 'stdf_add_invoice_template_page'));

			//Courier Score Modal
			add_action('admin_footer', array($this, 'render_courier_score_modal'));
		}


		public function render_courier_score_modal()
		{ ?>

			<div id="stdf-customer-info-modal">
				<h2><?php // echo esc_html__('📊 SteadFast Success Rate', 'steadfast-api'); ?></h2>
				<div id="stdf-customer-info-content">
				
				</div>
				<button id="stdf-close-modal"><?php echo esc_html__('Close', 'steadfast-api'); ?></button>
			</div>
			<div id="stdf-modal-overlay"></div>
			<?php
		}

		function stdf_add_invoice_template_page()
		{
			add_dashboard_page(esc_html__('SteadFast Invoice', 'steadfast-api'), esc_html__('SteadFast Invoice', 'steadfast-api'), 'manage_options', 'stdf-invoice', array($this, 'stdf_invoice_callback'));
		}

		function stdf_invoice_callback()
		{
			$order_id = isset($_GET['order_id']) ? sanitize_text_field(wp_unslash($_GET['order_id'])) : '';

			if (empty($order_id) || !(isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'stdf_print_order_nonce'))) {
				wp_redirect(home_url());
				exit();
			}
		}

		function stdf_invoice_template()
		{
			$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

			if ($page == 'stdf-invoice' && (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'stdf_print_order_nonce'))) {
				remove_action('wp_print_styles', 'print_emoji_styles');
				include_once STDF_PLUGIN_DIR . 'templates/invoice.php';
				exit();
			}
		}

		/**
		 * @return array
		 */
		function admin_orders_table_row_unlink($classes, $class, $post_id)
		{

			if (is_admin()) {
				$current_screen = get_current_screen();
				if ($current_screen->base == 'edit' && $current_screen->post_type == 'shop_order') {
					$classes[] = 'no-link';
				}
			}

			return $classes;
		}


		/**
		 * @param $links
		 * @param $file
		 * @param $plugin_data
		 * @param $context
		 *
		 * @return array|mixed
		 */
		function add_plugin_action_links($links, $file, $plugin_data, $context)
		{

			if ('dropins' === $context) {
				return $links;
			}

			$what = ('mustuse' === $context) ? 'muplugin' : 'plugin';
			$new_links = array();

			foreach ($links as $link_id => $link) {

				if ('deactivate' == $link_id && STDF_PLUGIN_FILE == $file) {
					$new_links['steadfast-settings'] = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=steadfast&tab=settings'), esc_html__('Settings', 'steadfast-api'));
				}

				$new_links[$link_id] = $link;
			}

			return $new_links;
		}

		/**
		 * Admin Order List Table Row Unlink
		 *
		 * @param $classes
		 *
		 * @return mixed
		 */
		function _admin_orders_table_row_unlink($classes)
		{
			$classes[] = 'no-link';

			return $classes;
		}


		/**
		 * Send bulks data to SteadFast.
		 *
		 * @param $bulk_actions
		 *
		 * @return void
		 */
		function register_bulk_action_send_steadfast($bulk_actions)
		{

			$checkbox = get_option('stdf_settings_tab_checkbox', false);

			if ($checkbox == 'yes') {

				$bulk_actions['send_to_steadFast_bulk'] = esc_html__('Send to SteadFast', 'steadfast-api');

				return $bulk_actions;
			}
		}

		/**
		 * Create custom column order dashboard.
		 *
		 * @param $columns
		 *
		 * @return array
		 */
		function add_steadfast_custom_column($columns)
		{

			$new_columns = array();

			foreach ($columns as $column_name => $column_info) {
				$new_columns[$column_name] = $column_info;


				if ('order_status' === $column_name) {
					$new_columns['amount'] = esc_html__('Amount', 'steadfast-api');
				}

				if ('order_status' === $column_name) {
					$new_columns['send_steadfast'] = esc_html__('Send to SteadFast', 'steadfast-api');
				}

				if ('order_status' === $column_name) {
					$new_columns['print_details'] = esc_html__('Invoice', 'steadfast-api');
				}

				if ('order_status' === $column_name) {
					$new_columns['consignment_id'] = esc_html__('ConsignmentID', 'steadfast-api');
				}

				if ('order_status' === $column_name) {
					$new_columns['delivery_status'] = esc_html__('DeliveryStatus', 'steadfast-api');
				}

				if ('order_status' === $column_name) {
					$new_columns['courier_score'] = esc_html__('Score', 'steadfast-api');
				}
			}

			return $new_columns;
		}

		/**
		 * @param $column
		 * @param $order
		 *
		 * @return void
		 */
		function add_custom_column_content_order_page($column, $order)
		{
			stdf_add_custom_column_content_order_page($column, $order);
		}

		/**
		 * @param $column
		 *
		 * @return void
		 */
		function add_custom_column_content_order_list_table($column)
		{
			stdf_add_custom_column_content_order_page($column);
		}

		/**
		 * @param $redirect
		 * @param $doaction
		 * @param $object_ids
		 *
		 * @return mixed|string
		 */
		function send_to_steadfast_bulk_process($redirect, $doaction, $object_ids)
		{
			return stdf_bulk_send_order($redirect, $doaction, $object_ids);
		}

		


		/**
		 * @return self|null
		 */
		public static function instance(
		) {
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

	}

}

STDF_Hooks::instance();
