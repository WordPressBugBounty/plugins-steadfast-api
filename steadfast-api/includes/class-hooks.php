<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'STDF_Hooks' ) ) {
	/**
	 * SteadFast Order Page
	 */
	class STDF_Hooks {

		protected static $_instance = null;

		public $success = '';

		function __construct() {

			// Register Bulk send order list table. WooCommerce - 7.0.0 version
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_action_send_steadfast' ) );
			add_action( 'handle_bulk_actions-edit-shop_order', array( $this, 'send_to_steadfast_bulk_process' ), 20, 3 );

			// Register Bulk send order list table. WooCommerce - Latest version
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'register_bulk_action_send_steadfast' ), 999 );
			add_action( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'send_to_steadfast_bulk_process' ), 20, 3 );

			// Add custom column order list table. WooCommerce - 7.0.0 version
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_steadfast_custom_column' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_custom_column_content_order_list_table' ) );

			// Add custom column content order list table. WooCommerce- Latest version
			add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_steadfast_custom_column' ) );
			add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'add_custom_column_content_order_page' ), 10, 2 );

			add_action( 'wp_ajax_api_ajax_call', array( $this, 'api_ajax_call' ) );
			add_action( 'wp_ajax_input_amount', array( $this, 'stdf_custom_amount_pay' ) );

			// List table row unlink. WooCommerce - 7.0.0 version
			add_filter( 'post_class', array( $this, 'admin_orders_table_row_unlink' ), 10, 3 );
			// List table row unlink. WooCommerce - Latest version
			add_filter( 'woocommerce_shop_order_list_table_order_css_classes', array( $this, '_admin_orders_table_row_unlink' ) );

			add_filter( 'plugin_action_links', array( $this, 'add_plugin_action_links' ), 10, 4 );
			add_action( 'init', array( $this, 'stdf_invoice_template' ) );
			add_action( 'admin_menu', array( $this, 'stdf_add_invoice_template_page' ) );
		}

		function stdf_add_invoice_template_page() {
			add_dashboard_page( esc_html__( 'SteadFast Invoice', 'steadfast-api'), esc_html__( 'SteadFast Invoice', 'steadfast-api'), 'manage_options', 'stdf-invoice', array( $this, 'stdf_invoice_callback' ) );
		}

		function stdf_invoice_callback() {
			$order_id = isset( $_GET['order_id'] ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : '';

			if ( empty( $order_id ) || ! ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'stdf_print_order_nonce' ) ) ) {
				wp_redirect( home_url() );
				exit();
			}
		}

		function stdf_invoice_template() {
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			if ( $page == 'stdf-invoice' && ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'stdf_print_order_nonce' ) ) ) {
				remove_action( 'wp_print_styles', 'print_emoji_styles' );
				include_once STDF_PLUGIN_DIR . 'templates/invoice.php';
				exit();
			}
		}

		/**
		 * @return array
		 */
		function admin_orders_table_row_unlink( $classes, $class, $post_id ) {

			if ( is_admin() ) {
				$current_screen = get_current_screen();
				if ( $current_screen->base == 'edit' && $current_screen->post_type == 'shop_order' ) {
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
		function add_plugin_action_links( $links, $file, $plugin_data, $context ) {

			if ( 'dropins' === $context ) {
				return $links;
			}

			$what      = ( 'mustuse' === $context ) ? 'muplugin' : 'plugin';
			$new_links = array();

			foreach ( $links as $link_id => $link ) {

				if ( 'deactivate' == $link_id && STDF_PLUGIN_FILE == $file ) {
					$new_links['steadfast-settings'] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=steadfast&tab=settings' ), esc_html__( 'Settings', 'steadfast-api') );
				}

				$new_links[ $link_id ] = $link;
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
		function _admin_orders_table_row_unlink( $classes ) {
			$classes[] = 'no-link';

			return $classes;
		}

		/**
		 * Get payment option value using ajax.
		 *
		 * @return void
		 */
		function stdf_custom_amount_pay() {
			$amount_nonce = isset( $_POST['stdf_amount_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['stdf_amount_nonce'] ) ) : '';
			$input_value  = isset( $_POST['input_value'] ) ? sanitize_text_field( wp_unslash( $_POST['input_value'] ) ) : '';
			$input_id     = isset( $_POST['input_id'] ) ? sanitize_text_field( wp_unslash( $_POST['input_id'] ) ) : '';

			if ( ! empty( $amount_nonce ) && wp_verify_nonce( $amount_nonce, 'stdf_amount' ) ) {
				$update = update_post_meta( $input_id, 'steadfast_amount', $input_value );
				if ( $update === true ) {
					wp_send_json_success( [ 'message' => esc_html__( 'success', 'steadfast-api') ], 200 );
				}
			}
		}

		/**
		 * Send order to steadfast.
		 * @return void
		 */
		function api_ajax_call() {

			$order_id    = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
			$order_nonce = isset( $_POST['order_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['order_nonce'] ) ) : '';

			if ( $order_id && $order_nonce ) {
				if ( wp_verify_nonce( $order_nonce, 'stdf_send_order' ) ) {
					$send = $this->call_steadfast_api( $order_id );
					if ( $send == 'success' ) {
						update_post_meta( $order_id, 'steadfast_is_sent', 'yes' );
						wp_send_json_success( [ 'message' => esc_html__( 'success', 'steadfast-api') ] );
					} else if ( $send == 'unauthorized' ) {
						wp_send_json_error( [ 'message' => esc_html__( 'unauthorized', 'steadfast-api') ] );
					} else {
						wp_send_json_error( [ 'message' => esc_html( $send ) ] );
					}
				} else {
					wp_send_json_error( [ 'message' => 'WP Nonce verifying failed!' ] );
				}
			} else {
				wp_send_json_error( [ 'message' => 'Invalid request parameters!' ] );
			}
		}

		/**
		 * Send bulks data to SteadFast.
		 *
		 * @param $bulk_actions
		 *
		 * @return void
		 */
		function register_bulk_action_send_steadfast( $bulk_actions ) {

			$checkbox = get_option( 'stdf_settings_tab_checkbox', false );

			if ( $checkbox == 'yes' ) {

				$bulk_actions['send_to_steadFast_bulk'] = esc_html__( 'Send to SteadFast', 'steadfast-api');

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
		function add_steadfast_custom_column( $columns ) {

			$new_columns = array();


			$checkbox = get_option( 'stdf_settings_tab_checkbox', false );

			foreach ( $columns as $column_name => $column_info ) {
				$new_columns[ $column_name ] = $column_info;


				if ( 'order_status' === $column_name ) {
					if ( $checkbox == 'yes' ) {
						$new_columns['amount'] = esc_html__( 'Amount', 'steadfast-api');
					}
				}

				if ( 'order_status' === $column_name ) {
					if ( $checkbox == 'yes' ) {
						$new_columns['send_steadfast'] = esc_html__( 'Send to SteadFast', 'steadfast-api');
					}
				}

				if ( 'order_status' === $column_name ) {
					if ( $checkbox == 'yes' ) {
						$new_columns['print_details'] = esc_html__( 'Print Details', 'steadfast-api');
					}
				}

				if ( 'order_status' === $column_name ) {
					if ( $checkbox == 'yes' ) {
						$new_columns['consignment_id'] = esc_html__( 'ConsignmentID', 'steadfast-api');
					}
				}

				if ( 'order_status' === $column_name ) {
					if ( $checkbox == 'yes' ) {
						$new_columns['delivery_status'] = esc_html__( 'DeliveryStatus', 'steadfast-api');
					}
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
		function add_custom_column_content_order_page( $column, $order ) {
			stdf_add_custom_column_content_order_page( $column, $order );
		}

		/**
		 * @param $column
		 *
		 * @return void
		 */
		function add_custom_column_content_order_list_table( $column ) {
			stdf_add_custom_column_content_order_page( $column );
		}

		/**
		 * @param $redirect
		 * @param $doaction
		 * @param $object_ids
		 *
		 * @return mixed|string
		 */
		function send_to_steadfast_bulk_process( $redirect, $doaction, $object_ids ) {
			return stdf_bulk_send_order( $redirect, $doaction, $object_ids );
		}

		/**
		 * Send Data To SteadFast Api.
		 *
		 * @param $order_id
		 *
		 * @return string
		 */
		function call_steadfast_api( $order_id ): string {

			$checkbox       = get_option( 'stdf_settings_tab_checkbox', false );
			$api_secret_key = get_option( 'api_settings_tab_api_secret_key', false );
			$api_key        = get_option( 'api_settings_tab_api_key', false );
			$api_notes      = get_option( 'stdf_settings_tab_notes', false );

			$order      = new WC_Order( $order_id );
			$order_data = $order->get_data();

			$input_amount = get_post_meta( $order_id, 'steadfast_amount', true );
			$input_amount = ! empty( $input_amount ) || $input_amount == 0 ? $input_amount : $order_data['total'];


			$fast_name               = $order_data['billing']['first_name'];
			$last_name               = $order_data['billing']['last_name'];
			$order_billing_address   = $order_data['billing']['address_1'];
			$order_billing_phone     = $order_data['billing']['phone'];
			$order_shipping_city     = $order_data['billing']['city'];
			$order_shipping_postcode = $order_data['billing']['postcode'];

			$order_note = $api_notes == 'yes' ? $order->get_customer_note() : '';

			//Check Customer Valid Phone Number.
			$n              = 10;
			$number         = strlen( $order_billing_phone ) - $n;
			$phone          = substr( $order_billing_phone, $number );
			$customer_phone = '0' . $phone;

			$recipient_address = $order_billing_address . ',' . $order_shipping_city . '-' . $order_shipping_postcode;
			$body              = array(
				"invoice"           => gmdate( "ymj" ) . '-' . $order_id,
				"recipient_name"    => $fast_name . ' ' . $last_name,
				"recipient_phone"   => $customer_phone,
				"recipient_address" => $recipient_address,
				"cod_amount"        => $input_amount,
				"note"              => $order_note,
			);

			$args = array(
				'method'      => 'POST',
				'headers'     => array(
					'content-type' => 'application/json',
					'api-key'      => sanitize_text_field( $api_key ),
					'secret-key'   => sanitize_text_field( $api_secret_key ),
				),
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'body'        => wp_json_encode( $body ),
				'cookies'     => array()
			);
			if ( $checkbox == 'yes' ) {
				$response = wp_remote_post( 'https://portal.packzy.com/api/v1/create_order', $args );

				$request = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $request['status'] ) && $request['status'] == 400 && isset( $request['errors'] ) ) {
					$errors = $request['errors'];

					foreach ( $errors as $field => $messages ) {
						foreach ( $messages as $message ) {
							return $message;
						}
					}
				}

				if ( $request['status'] == 200 ) {
					$consignment_id = $request['consignment']['consignment_id'];
					update_post_meta( $order_id, 'steadfast_consignment_id', $consignment_id );

					return esc_html__( 'success', 'steadfast-api');
				}
			}

			return esc_html__( 'unauthorized', 'steadfast-api');
		}


		/**
		 * @return self|null
		 */
		public
		static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

	}

}

STDF_Hooks::instance();
