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

			// Webhook registration
			add_action('rest_api_init', array($this, 'stdf_register_webhook_route'));

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

			if (is_admin() && function_exists('get_current_screen')) {
				$current_screen = get_current_screen();
				if ($current_screen && $current_screen->base == 'edit' && $current_screen->post_type == 'shop_order') {
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
			}

			return $bulk_actions;
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

		/**
		 * Register the SteadFast REST API webhook route.
		 */
		public function stdf_register_webhook_route()
		{
			register_rest_route('stdf-api/v1', '/webhook', array(
				'methods'             => 'POST',
				'callback'            => array($this, 'stdf_webhook_callback_handler'),
				'permission_callback' => '__return_true',
			));
		}

		/**
		 * Handles incoming SteadFast Webhook payloads.
		 *
		 * @param WP_REST_Request $request
		 * @return WP_Error|WP_REST_Response
		 */
		public function stdf_webhook_callback_handler($request)
		{
			$token = get_option('stdf_webhook_token');
			if (empty($token)) {
				return new WP_Error('rest_forbidden', esc_html__('Webhook token not configured.', 'steadfast-api'), array('status' => 403));
			}

			// Get bearer token from Authorization Header
			$auth_header = $request->get_header('authorization');
			$provided_token = '';
			if (!empty($auth_header) && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
				$provided_token = trim($matches[1]);
			}

			// Fallback to URL query parameter if Authorization header is stripped by host
			if (empty($provided_token)) {
				$provided_token = $request->get_param('token');
			}

			// Validate token
			if (empty($provided_token) || $provided_token !== $token) {
				return new WP_Error('rest_forbidden', esc_html__('Unauthorized webhook access.', 'steadfast-api'), array('status' => 401));
			}

			// Extract payload parameters
			$params = $request->get_params();
			$consignment_id = isset($params['consignment_id']) ? sanitize_text_field($params['consignment_id']) : '';
			$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

			// Fallback to tracking_code if consignment_id is absent
			if (empty($consignment_id) && isset($params['tracking_code'])) {
				$consignment_id = sanitize_text_field($params['tracking_code']);
			}

			if (empty($consignment_id) || empty($status)) {
				return new WP_Error('rest_invalid_params', esc_html__('Missing consignment_id or status parameter.', 'steadfast-api'), array('status' => 400));
			}

			// Find order ID by consignment_id meta
			global $wpdb;
			// Find order ID by consignment_id meta (Search both tables to guarantee compatibility)
			$order_id = (int) $wpdb->get_var($wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'steadfast_consignment_id' AND meta_value = %s LIMIT 1",
				$consignment_id
			));

			if (!$order_id) {
				$hpos_table = $wpdb->prefix . 'wc_orders_meta';
				if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hpos_table)) === $hpos_table) {
					$order_id = (int) $wpdb->get_var($wpdb->prepare(
						"SELECT order_id FROM {$hpos_table} WHERE meta_key = 'steadfast_consignment_id' AND meta_value = %s LIMIT 1",
						$consignment_id
					));
				}
			}

			if (!$order_id) {
				return new WP_Error('rest_not_found', esc_html__('No order found matching the provided consignment ID.', 'steadfast-api'), array('status' => 404));
			}

			// Update order delivery status
			update_post_meta($order_id, 'stdf_delivery_status', $status);
			update_option('stdf_last_webhook_received', current_time('mysql'));

			// Fire custom action hook for extensibility
			do_action('stdf_webhook_delivery_status_updated', $order_id, $status, $consignment_id);

			return new WP_REST_Response(array(
				'success'        => true,
				'message'        => 'Delivery status updated successfully.',
				'order_id'       => $order_id,
				'consignment_id' => $consignment_id,
				'status'         => $status
			), 200);
		}

	}

}

STDF_Hooks::instance();
