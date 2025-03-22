<?php

defined('ABSPATH') || exit;

if (!class_exists('STDF_Ajax')) {

    class STDF_Ajax
    {
        protected static $_instance = null;

        function __construct()
        {
            add_action('wp_ajax_get_order_info', array($this, 'check_order_scores'));
            add_action('wp_ajax_stdf_delivery_status', array($this, 'check_delivery_status'));
            add_action('wp_ajax_std_current_balance', array($this, 'check_current_balance'));
            add_action('wp_ajax_input_amount', array($this, 'input_custom_amount'));
            add_action('wp_ajax_send_to_steadfast', array($this, 'send_to_steadfast'));
        }

        /**
         * Send order to steadfast.
         * @return void
         */
        function send_to_steadfast()
        {

            $order_id = isset($_POST['order_id']) ? sanitize_text_field(wp_unslash($_POST['order_id'])) : '';
            $order_nonce = isset($_POST['order_nonce']) ? sanitize_text_field(wp_unslash($_POST['order_nonce'])) : '';
           
            if ($order_id && $order_nonce) {
                if (wp_verify_nonce($order_nonce, 'stdf_send_order')) {
                    $send = send_order_to_steadfast_api($order_id);
                    if ($send == 'success') {
                        update_post_meta($order_id, 'steadfast_is_sent', 'yes');
                        wp_send_json_success(['message' => esc_html__('success', 'steadfast-api')]);
                    } else if ($send == 'unauthorized') {
                        wp_send_json_error(['message' => esc_html__('unauthorized', 'steadfast-api')]);
                    } else {
                        wp_send_json_error(['message' => esc_html($send)]);
                    }
                } else {
                    wp_send_json_error(['message' => 'WP Nonce verifying failed!']);
                }
            } else {
                wp_send_json_error(['message' => 'Invalid request parameters!']);
            }
        }


        /**
         * Get payment option value using ajax.
         *
         * @return void
         */
        function input_custom_amount()
        {
            $amount_nonce = isset($_POST['stdf_amount_nonce']) ? sanitize_text_field(wp_unslash($_POST['stdf_amount_nonce'])) : '';
            $input_value = isset($_POST['input_value']) ? sanitize_text_field(wp_unslash($_POST['input_value'])) : '';
            $input_id = isset($_POST['input_id']) ? sanitize_text_field(wp_unslash($_POST['input_id'])) : '';

            if (!empty($amount_nonce) && wp_verify_nonce($amount_nonce, 'stdf_amount')) {
                $update = update_post_meta($input_id, 'steadfast_amount', $input_value);
                if ($update === true) {
                    wp_send_json_success(['message' => esc_html__('success', 'steadfast-api')], 200);
                }
            }
        }


        /**
         * @return void
         */
        function check_current_balance()
        {

            $value      = isset($_POST['value']) ? sanitize_text_field(wp_unslash($_POST['value'])) : '';
            $stdf_nonce = isset($_POST['stdf_nonce']) ? sanitize_text_field(wp_unslash($_POST['stdf_nonce'])) : '';

            if (! empty($value) && wp_verify_nonce($stdf_nonce, 'stdf-balance-verify')) {

                $response = stdf_check_current_balance($value);

                if ($response == 'unauthorized') {
                    $data = 'unauthorized';
                } else if ($response !== 'failed') {
                    $data = $response['current_balance'];
                } else {
                    $data = 'failed';
                }

                wp_send_json_success($data, 200);
            }
        }


        function check_delivery_status()
        {

            $consignment_id = isset($_POST['consignment_id']) ? sanitize_text_field(wp_unslash($_POST['consignment_id'])) : '';
            $order_id       = isset($_POST['order_id']) ? sanitize_text_field(wp_unslash($_POST['order_id'])) : '';
            $stdf_nonce     = isset($_POST['stdf_nonce']) ? sanitize_text_field(wp_unslash($_POST['stdf_nonce'])) : '';

            if (! empty($consignment_id) && ! empty($order_id) && wp_verify_nonce($stdf_nonce, 'stdf_delivery_status_nonce')) {
                $response = stdf_get_status_by_consignment_id($consignment_id);

                if ($response == 'unauthorized') {
                    $data = 'unauthorized';
                } else if ($response !== 'failed') {
                    $data = $response['delivery_status'];
                    update_post_meta($order_id, 'stdf_delivery_status', $data);
                } else {
                    $data = $response;
                }

                wp_send_json_success($data, 200);
            }
        }

        public function check_order_scores()
        {
            $stdf_nonce     = isset($_POST['stdf_nonce']) ? sanitize_text_field(wp_unslash($_POST['stdf_nonce'])) : '';

            if (!$stdf_nonce || !wp_verify_nonce($stdf_nonce, 'stdf_courier_score_nonce')) {
                wp_send_json_error('Invalid nonce');
            }

            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

            if (!$order_id) {
                wp_send_json_error('Invalid order ID');
            }

            $order = wc_get_order($order_id);

            if (!$order) {
                wp_send_json_error('Order not found');
            }

            $mobile_number = $order->get_billing_phone();

            $order_info = stdf_customer_courier_score($mobile_number,$order_id);

            wp_send_json_success($order_info);
        }

        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }
    }

    STDF_Ajax::instance();
}
