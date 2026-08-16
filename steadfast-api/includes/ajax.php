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
            add_action('wp_ajax_save_steadfast_settings', array($this, 'save_steadfast_settings'));
            add_action('wp_ajax_stdf_test_connection', array($this, 'test_api_connection'));
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
                        
                        $consignment_id = get_post_meta($order_id, 'steadfast_consignment_id', true);
                        
                        // Build site URL for print details
                        $site_url = add_query_arg(
                            array(
                                'order_id'       => $order_id,
                                'consignment_id' => $consignment_id,
                            ),
                            admin_url('/index.php?page=stdf-invoice')
                        );
                        $nonce_url = wp_nonce_url($site_url, 'stdf_print_order_nonce');
                        $print_html = sprintf('<div><a class="std-print-order-detail" target="_blank" href="%s">%s</a></div>', esc_url(urldecode($nonce_url)), esc_html__('Print', 'steadfast-api'));
                        
                        // Build delivery status HTML
                        $status_nonce = wp_create_nonce('stdf_delivery_status_nonce');
                        ob_start();
                        ?>
                        <div class="std-order-status">
                            <button id="std-delivery-status" data-stdf-status="<?php echo esc_attr($status_nonce); ?>" data-order-id="<?php echo esc_attr($order_id); ?>" data-consignment-id="<?php echo esc_attr($consignment_id); ?>"><?php echo esc_html__('Check', 'steadfast-api'); ?></button>
                            <div id="std-re-check-delivery-status" class="hidden dashicons dashicons-image-rotate" data-stdf-status="<?php echo esc_attr($status_nonce); ?>" data-order-id="<?php echo esc_attr($order_id); ?>" data-consignment-id="<?php echo esc_attr($consignment_id); ?>"></div>
                            <span id="std-current-status" data-status-id="<?php echo esc_attr($order_id); ?>" class="hidden"></span>
                        </div>
                        <?php
                        $delivery_html = ob_get_clean();

                        wp_send_json_success([
                            'message'        => esc_html__('success', 'steadfast-api'),
                            'consignment_id' => esc_html($consignment_id),
                            'print_html'     => $print_html,
                            'delivery_html'  => $delivery_html
                        ]);
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
                update_post_meta($input_id, 'steadfast_amount', $input_value);
                wp_send_json_success(['message' => esc_html__('success', 'steadfast-api')], 200);
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
                }else {
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

        public function save_steadfast_settings()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => esc_html__('Unauthorized capability.', 'steadfast-api')]);
            }

            $nonce = isset($_POST['stdf_settings_nonce_field']) ? sanitize_text_field(wp_unslash($_POST['stdf_settings_nonce_field'])) : '';
            if (!$nonce || !wp_verify_nonce($nonce, 'stdf_settings_nonce')) {
                wp_send_json_error(['message' => esc_html__('Security verification failed.', 'steadfast-api')]);
            }

            $text_fields = array(
                'api_settings_tab_api_key',
                'api_settings_tab_api_secret_key',
                'stdf_business_name',
                'stdf_business_address',
                'stdf_business_email',
                'stdf_business_number',
                'stdf_term_condition'
            );

            foreach ($text_fields as $field) {
                if (isset($_POST[$field])) {
                    $val = sanitize_text_field(wp_unslash($_POST[$field]));
                    update_option($field, $val);
                }
            }

            $checkbox_fields = array(
                'stdf_settings_tab_checkbox',
                'stdf_settings_tab_notes'
            );

            foreach ($checkbox_fields as $field) {
                $val = isset($_POST[$field]) && $_POST[$field] === 'yes' ? 'yes' : '';
                update_option($field, $val);
            }

            $logo_url = '';
            if (!empty($_FILES) && isset($_FILES['stdf_business_logo']) && $_FILES['stdf_business_logo']['size'] > 0) {
                if (!function_exists('wp_handle_upload')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }

                $uploaded_image = wp_handle_upload($_FILES['stdf_business_logo'], array('test_form' => false));
                if (isset($uploaded_image['url'])) {
                    $logo_url = $uploaded_image['url'];
                    update_option('stdf_business_logo', $logo_url);
                } elseif (isset($uploaded_image['error'])) {
                    wp_send_json_error(['message' => $uploaded_image['error']]);
                }
            }

            if (empty($logo_url)) {
                $logo_url = get_option('stdf_business_logo', '');
            }

            wp_send_json_success([
                'message' => esc_html__('Settings saved successfully!', 'steadfast-api'),
                'logo_url' => $logo_url
            ]);
        }

        public function test_api_connection()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => esc_html__('Unauthorized capability.', 'steadfast-api')]);
            }

            $api_key = trim(get_option('api_settings_tab_api_key', ''));
            $api_secret = trim(get_option('api_settings_tab_api_secret_key', ''));

            if (empty($api_key) || empty($api_secret)) {
                wp_send_json_error(['message' => esc_html__('Credentials Missing', 'steadfast-api')]);
            }

            $balance = stdf_check_current_balance('check-yes');

            if ($balance === 'unauthorized' || $balance === 'failed' || is_wp_error($balance)) {
                wp_send_json_error(['message' => esc_html__('Connection Inactive', 'steadfast-api')]);
            }

            wp_send_json_success(['message' => esc_html__('Connection Active', 'steadfast-api')]);
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
