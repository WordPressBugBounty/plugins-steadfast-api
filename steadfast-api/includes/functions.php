<?php

/**
 * All functions
 */

if ( ! function_exists( 'stdf_get_order_customer_details' ) ) {
	/**
	 * @param $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	function stdf_get_order_customer_details( $order_id ): array {
		$order         = wc_get_order( $order_id );
		$order_details = array();

		if ( $order ) {

			$order_data     = $order->get_data();
			$payment_method = $order->get_payment_method_title() ?? '';

			$customer_id = $order->get_customer_id();
			$customer    = new WC_Customer( $customer_id );

			$input_amount = get_post_meta( $order_id, 'steadfast_amount', true );
			$cod_amount   = ! empty( $input_amount ) || $input_amount == 0 ? (int) $input_amount : (int) $order_data['total'];

			$total              = $order->get_total();
			$billing_first_name = $order->get_billing_first_name() ?? '';
			$billing_last_name  = $order->get_billing_last_name() ?? '';
			$full_name          = $billing_first_name . ' ' . $billing_last_name;
			$email              = $customer->get_email();
			$customer_phone     = $customer->get_billing_phone();

			$address_info = [];

			$billing_address_1 = $customer->get_billing_address_1();
			if ( ! empty( $billing_address_1 ) ) {
				$address_info[] = $billing_address_1;
			}

			$billing_city = $customer->get_billing_city();
			if ( ! empty( $billing_city ) ) {
				$address_info[] = $billing_city;
			}

			$billing_postcode = $customer->get_billing_postcode();
			if ( ! empty( $billing_postcode ) ) {
				$address_info[] = $billing_postcode;
			}

			$billing_country = $customer->get_billing_country();
			if ( ! empty( $billing_country ) ) {
				$address_info[] = $billing_country;
			}

			$order_details = array(
				'customer_name'    => $full_name,
				'customer_email'   => $email,
				'customer_phone'   => $customer_phone,
				'customer_address' => $address_info,
				'cod_amount'       => $cod_amount,
				'payment_method'   => $payment_method,
			);

		}

		return $order_details;
	}
}

if ( ! function_exists( 'stdf_get_product_details' ) ) {
	/**
	 * @param $order_id
	 *
	 * @return array
	 */
	function stdf_get_product_details( $order_id ) {
		$order = wc_get_order( $order_id );
		$data  = array();

		if ( $order ) {
			foreach ( $order->get_items() as $item_id => $item ) {

				$product     = $item->get_product() ?? '';
				$name        = $item->get_name() ?? '';
				$quantity    = $item->get_quantity() ?? '';
				$subtotal    = $item->get_subtotal() ?? '';
				$price       = $product->get_price() ?? '';
				$description = get_post( $item['product_id'] )->post_content;


				$words = explode( ' ', $description );
				if ( count( $words ) > 7 ) {
					$words      = array_slice( $words, 0, 7 );
					$short_desc = implode( ' ', $words ) . '...';
				}

				$data[] = array(
					'name'        => $name,
					'quantity'    => $quantity,
					'subtotal'    => $subtotal,
					'price'       => $price,
					'description' => $short_desc,
				);
			}
		}

		return $data;
	}
}

if ( ! function_exists( 'stdf_get_shipping_cost' ) ) {
	/**
	 * @param $order_id
	 *
	 * @return string
	 */
	function stdf_get_shipping_cost( $order_id ) {

		$order          = wc_get_order( $order_id );
		$shipping_total = $order->get_shipping_total();

		if ( $shipping_total ) {
			return $shipping_total;
		} else {
			return 00;
		}
	}
}

if ( ! function_exists( 'stdf_get_product_sku_id' ) ) {
	/**
	 * @param $order_id
	 *
	 * @return array
	 */
	function stdf_get_product_sku_id( $order_id ) {
		$item_sku = array();
		$order    = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			$product    = wc_get_product( $item->get_product_id() );
			$item_sku[] = $product->get_sku();
		}

		return $item_sku;
	}
}

if ( ! function_exists( 'stdf_get_status_by_consignment_id' ) ) {
	/**
	 * @param $consignment_id
	 *
	 * @return mixed|string
	 */
	function stdf_get_status_by_consignment_id( $consignment_id ) {

		$checkbox       = get_option( 'stdf_settings_tab_checkbox', false );
		$api_secret_key = get_option( 'api_settings_tab_api_secret_key', false );
		$api_key        = get_option( 'api_settings_tab_api_key', false );

		$args = array(
			'method'      => 'GET',
			'headers'     => array(
				'content-type' => 'application/json',
				'api-key'      => sanitize_text_field( $api_key ),
				'secret-key'   => sanitize_text_field( $api_secret_key ),
			),
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'cookies'     => array()
		);

		if ( $checkbox == 'yes' ) {
			$response = wp_remote_get( 'https://portal.packzy.com/api/v1/status_by_cid/' . $consignment_id, $args );

			$request = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( $request['status'] == '200' ) {
				return $request;
			} else {
				return 'unauthorized';
			}
		} else {
			return 'failed';
		}
	}
}

if ( ! function_exists( 'stdf_add_custom_column_content_order_page' ) ) {
	/**
	 * @param $column
	 * @param $order
	 *
	 * @return void
	 */
	function stdf_add_custom_column_content_order_page( $column, $order = '' ) {

		if ( empty( $order ) ) {
			global $post, $stdf_the_order;


			if ( empty( $stdf_the_order ) || ! is_a( $stdf_the_order, 'WC_Order' ) || $stdf_the_order->get_id() !== $post->ID ) {
				$stdf_the_order = wc_get_order( $post->ID );
			}

			$order_id = is_a( $stdf_the_order, 'WC_Order' ) ? $stdf_the_order->get_id() : null;
		} else {
			$order_id = $order->get_id();
		}

		$meta_value = get_post_meta( $order_id, 'steadfast_is_sent', true );
		$classes    = 'yes' == $meta_value ? esc_html__( 'steadfast-send-success', 'steadfast-api') : esc_html__( 'steadfast_send', 'steadfast-api');


		$checkbox = get_option( 'stdf_settings_tab_checkbox', false );

		if ( $checkbox == 'yes' ) {
			if ( 'send_steadfast' === $column ) {
				?>
                <button class="<?php echo esc_attr( $classes ) ?>" data-stdf-order-nonce="<?php echo esc_attr( wp_create_nonce( 'stdf_send_order' ) ) ?>" data-order-id="<?php echo esc_attr( $order_id ) ?>" name="steadfast"><?php echo esc_html__( 'Send', 'steadfast-api'); ?></button>
				<?php
			}

			$consignment_id = ( get_post_meta( $order_id, 'steadfast_consignment_id', true ) ) ? esc_html( get_post_meta( $order_id, 'steadfast_consignment_id', true ) ) : '';

			$site_url = add_query_arg(
				array(
					'order_id'       => $order_id,
					'consignment_id' => $consignment_id,
				),
				admin_url( '/index.php?page=stdf-invoice' )
			);

			if ( ! empty( $consignment_id ) ) {
				if ( 'consignment_id' === $column ) {
					printf( '<div class="std-consignment-id">%s</div>', esc_html( $consignment_id ) );
				}

				if ( 'print_details' === $column ) {
					$nonce_url = wp_nonce_url( $site_url, 'stdf_print_order_nonce' );
					printf( '<div ><a class="std-print-order-detail" target="_blank" href="%s">%s</a></div>', esc_url( urldecode( $nonce_url ) ), esc_html__( 'Print', 'steadfast-api') );
				}

				$delivery_status = get_post_meta( $order_id, 'stdf_delivery_status', true );
				$status          = ucfirst( $delivery_status ) ?? '';

				$explode      = explode( '_', $delivery_status );
				$implode      = implode( '-', $explode );
				$status_class = ! empty( $implode ) ? 'std-' . $implode : '';

				if ( 'delivery_status' === $column ) { ?>
					<?php if ( empty( $delivery_status ) ) { ?>
                        <div class="std-order-status">
                            <button id="std-delivery-status" data-stdf-status="<?php echo esc_attr( wp_create_nonce( 'stdf_delivery_status_nonce' ) ) ?>" data-order-id="<?php echo esc_attr( $order_id ); ?>" data-consignment-id="<?php echo esc_attr( $consignment_id ); ?>"><?php echo esc_html__( 'Check', 'steadfast-api'); ?></button>
                            <div id="std-re-check-delivery-status" class=" hidden dashicons dashicons-image-rotate" data-stdf-status="<?php echo esc_attr( wp_create_nonce( 'stdf_delivery_status_nonce' ) ) ?>" data-order-id="<?php echo esc_attr( $order_id ); ?>" data-consignment-id="<?php echo esc_attr( $consignment_id ); ?>"></div>
                            <span id="std-current-status" data-status-id="<?php echo esc_attr( $order_id ); ?>" class="hidden <?php echo esc_attr( $status_class ) ?>"><?php echo esc_html( $status ); ?></span>
                        </div>
					<?php } else { ?>
                        <div class="std-order-status">
                            <div id="std-re-check-delivery-status" class="dashicons dashicons-image-rotate" data-stdf-status="<?php echo esc_attr( wp_create_nonce( 'stdf_delivery_status_nonce' ) ) ?>" data-order-id="<?php echo esc_attr( $order_id ); ?>" data-consignment-id="<?php echo esc_attr( $consignment_id ); ?>"></div>
                            <span id="std-current-status" data-status-id="<?php echo esc_attr( $order_id ); ?>" class="<?php echo esc_attr( $status_class ) ?>"><?php echo esc_html( $status ); ?></span>
                        </div>
					<?php }
				}
			}

			$amnt_class  = $meta_value == 'yes' ? 'amount-disable' : '';
			$input_value = get_post_meta( $order_id, 'steadfast_amount', true );

			if ( 'amount' === $column ) { ?>
                <input type="text" id="steadfast-amount" data-stdf-amount="<?php echo esc_attr( wp_create_nonce( 'stdf_amount' ) ) ?>" name="steadfast-amount" class="<?php echo esc_attr( $amnt_class ); ?>" value="<?php echo esc_attr( $input_value ); ?>" data-order-id="<?php echo esc_attr( $order_id ); ?>" style="width: 80px">
			<?php }
		}
	}
}

if ( ! function_exists( 'stdf_bulk_send_order' ) ) {
	/**
	 * @param $redirect
	 * @param $doaction
	 * @param $object_ids
	 *
	 * @return mixed|string
	 */
	function stdf_bulk_send_order( $redirect, $doaction, $object_ids ) {

		$call_api = new STDF_Hooks();

		if ( 'send_to_steadFast_bulk' === $doaction ) {
			foreach ( $object_ids as $order_id ) {
				$sent = $call_api->call_steadfast_api( $order_id );
				if ( $sent == 'success' ) {
					update_post_meta( $order_id, 'steadfast_is_sent', 'yes' );
				}
			}

			$redirect = add_query_arg(
				array(
					'bulk_action' => 'send_to_steadFast_bulk',
					'changed'     => count( $object_ids ),
				),
				$redirect
			);
		}

		return $redirect;
	}
}
