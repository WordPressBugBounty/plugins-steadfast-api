<?php

defined( 'ABSPATH' ) || exit;

$order_id = isset( $_GET['order_id'] ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : '';

if ( empty( $order_id ) || ! ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'stdf_print_order_nonce' ) ) ) {
	wp_redirect( home_url() );
	exit();
}

$order_id       = isset( $_GET['order_id'] ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : '';
$consignment_id = isset( $_GET['consignment_id'] ) ? sanitize_text_field( wp_unslash( $_GET['consignment_id'] ) ) : '';

$stdf_business_name    = get_option( 'stdf_business_name' ) ?? '';
$stdf_business_address = get_option( 'stdf_business_address' ) ?? '';
$stdf_business_email   = get_option( 'stdf_business_email' ) ?? '';
$business_logo         = get_option( 'stdf_business_logo' ) ?? '';


$customer        = stdf_get_order_customer_details( $order_id ) ?? '';
$product_details = stdf_get_product_details( $order_id ) ?? '';

$order          = wc_get_order( $order_id );
$shipping_total = $order->get_shipping_total();

$sku = stdf_get_product_sku_id( $order_id );

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo esc_html( 'SteadFast Invoice' ); ?></title>
	<?php wp_head(); ?>
</head>

<body class="steadfast-invoice">
<div class="tm_container">
    <div class="tm_invoice_wrap">
        <div class="tm_invoice tm_style1" id="tm_download_section">
            <div class="tm_invoice_in">
                <div class="tm_invoice_head tm_align_center tm_mb20">
                    <div class="tm_invoice_left">
                        <div class="tm_logo"><img src="<?php echo esc_url( $business_logo ); ?>" alt="<?php echo esc_html__( 'Business Logo', 'steadfast-api') ?>"></div>
                    </div>
                    <div class="tm_invoice_right tm_text_right">
                        <div class="tm_primary_color tm_f30 tm_text_uppercase"><?php echo esc_html__( 'Invoice', 'steadfast-api') ?></div>
                    </div>
                </div>
                <div class="tm_invoice_info tm_mb20">
                    <div class="tm_invoice_seperator"></div>
                    <div class="tm_invoice_info_list">
                        <p class="tm_invoice_number tm_m0"><?php echo esc_html__( 'Invoice No: #', 'steadfast-api') ?> <b class="tm_primary_color"><?php echo esc_html( $order_id, ) ?></b></p>
                        <p class="tm_invoice_date tm_m0"><?php echo esc_html__( 'Date: ', 'steadfast-api') ?> <b class="tm_primary_color"><?php echo esc_html( gmdate( 'd-m-y' ) ); ?></b></p>
                    </div>
                </div>
                <div class="tm_invoice_head tm_mb10">
                    <div class="tm_invoice_left">
                        <p class="tm_mb2"><b class="tm_primary_color"><?php echo esc_html__( 'Invoice To: ', 'steadfast-api') ?></b></p>
                        <p>
							<?php echo esc_html( $customer['customer_name'] ) ?>
                            <br>
							<?php $address_info = $customer['customer_address'];
							$address            = implode( ', ', $address_info );
							echo esc_html( $address ); ?>
                            <br>
							<?php echo esc_html( $customer['customer_email'] ) ?>
                            <br>
							<?php echo esc_html( $customer['customer_phone'] ) ?>
                        </p>
                    </div>
                    <div class="tm_invoice_right tm_text_right">
                        <p class="tm_mb2"><b class="tm_primary_color"><?php echo esc_html__( 'Pay To: ', 'steadfast-api') ?></b></p>
                        <p>
							<?php echo esc_html( $stdf_business_name ) ?>
                            <br>
							<?php echo esc_html( $stdf_business_address ) ?>
                            <br>
							<?php echo esc_html( $stdf_business_email ) ?>
                        </p>
                    </div>
                </div>
                <div class="std-consignment">
                    <div class="tm_padd_15_20 tm_round_border">
                        <div class="tm_mb10 tm_invoice_head tm_align_center">
                            <div class="tm_invoice_left">
                                <p class="tm_mb3"><b class="tm_primary_color"><?php echo esc_html__( 'For SteadFast Courier LTD', 'steadfast-api') ?></b></p>
                            </div>
                            <div class="tm_invoice_right tm_text_right invoice_img stdf-courier-logo">
                                <img src="<?php echo esc_url( esc_url_raw( STDF_PLUGIN_URL ) . '/assets/admin/img/logo.png' ); ?>" alt="<?php echo esc_html__( 'SteadFast Logo', 'steadfast-api') ?>">
                            </div>
                        </div>
                        <hr class="hr">
                        <div class="tm_invoice_head tm_mb10">
                            <div class="tm_invoice_left">
                                <h5 class="tm_mb2"><b class="tm_primary_color"> <?php echo esc_html( 'CN ID: #' . $consignment_id ) ?></b></h5>
                            </div>
                            <div class="tm_invoice_right tm_text_right">
                                <h5 class="tm_mb2"><b class="tm_primary_color">
										<?php echo esc_html( 'COD Amount: &#2547;' . $customer['cod_amount'] ) ?>
                                    </b></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tm_table tm_style1 tm_mb30">
                    <div class="tm_round_border">
                        <div class="tm_table_responsive">
                            <table>
                                <thead>
                                <tr>
                                    <th class="tm_width_3 tm_semi_bold tm_primary_color tm_gray_bg"><?php echo esc_html__( 'Item', 'steadfast-api'); ?></th>
                                    <th class="tm_width_4 tm_semi_bold tm_primary_color tm_gray_bg"><?php echo esc_html__( 'Description', 'steadfast-api'); ?></th>
                                    <th class="tm_width_2 tm_semi_bold tm_primary_color tm_gray_bg"><?php echo esc_html__( 'Price', 'steadfast-api'); ?></th>
                                    <th class="tm_width_1 tm_semi_bold tm_primary_color tm_gray_bg"><?php echo esc_html__( 'Qty', 'steadfast-api'); ?></th>
                                    <th class="tm_width_2 tm_semi_bold tm_primary_color tm_gray_bg"><?php echo esc_html__( 'Total', 'steadfast-api'); ?></th>
                                </tr>
                                </thead>
                                <tbody>

								<?php foreach ( $product_details as $product ) : ?>
                                    <tr>
                                        <td class="tm_width_3"> <?php echo esc_html( $product['name'] ); ?>  </td>
                                        <td class="tm_width_4">  <?php echo esc_html( $product['description'] ); ?> </td>
                                        <td class="tm_width_2"> <?php echo esc_html( '&#2547;' . $product['price'] ); ?> </td>
                                        <td class="tm_width_1"> <?php echo esc_html( $product['quantity'] ); ?></td>
                                        <td class="tm_width_2 tm_text_right">  <?php echo esc_html( '&#2547;' . $product['subtotal'] ); ?></td>
                                    </tr>
								<?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tm_invoice_footer std-payment-info">
                        <div class="tm_left_footer">
                            <p class="tm_mb2"><b class="tm_primary_color"><?php echo esc_html__( 'Payment info: ', 'steadfast-api') ?></b></p>
                            <p class="tm_m0"><?php echo esc_html( $customer['payment_method'] ); ?></p>
                        </div>
                        <div class="tm_right_footer">
                            <table>
                                <tbody>
                                <tr>
                                    <td class="tm_width_3 tm_primary_color tm_border_none tm_bold"><?php echo esc_html__( 'Subtotal', 'steadfast-api'); ?></td>
                                    <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_bold">
										<?php
										$order    = wc_get_order( $order_id );
										$subtotal = $order->get_subtotal();
										echo esc_html( '&#2547;' . $subtotal );
										?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="tm_width_3 tm_primary_color tm_border_none tm_pt0"><?php echo esc_html__( 'Shipping cost', 'steadfast-api') ?></td>
                                    <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_pt0"><?php echo esc_html( $shipping_total ) . '&#2547;' ?></td>
                                </tr>
                                <tr>
                                    <td class="tm_width_3 tm_primary_color tm_border_none tm_pt0"><?php echo esc_html__( 'Tax', 'steadfast-api') ?> <span class="tm_ternary_color">(0%)</span></td>
                                    <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_pt0"><?php echo esc_html__( '00 &#2547;', 'steadfast-api') ?></td>
                                </tr>
								<?php $grand_total = ( $shipping_total ) ? $subtotal + (float) $shipping_total : $subtotal; ?>
                                <tr class="tm_border_top tm_border_bottom">
                                    <td class="tm_width_3 tm_border_top_0 tm_border_bottom tm_bold tm_f16 tm_primary_color"><?php echo esc_html__( 'Grand Total', 'steadfast-api') ?></td>
                                    <td class="tm_width_3 tm_border_top_0 tm_border_bottom tm_bold tm_f16 tm_primary_color tm_text_right"><?php echo esc_html( '&#2547;' . $grand_total ); ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tm_padd_15_20 tm_round_border">
                    <p class="tm_mb5"><b class="tm_primary_color"><?php echo esc_html__( 'Terms & Conditions: ', 'steadfast-api') ?> </b></p>
                    <p>
						<?php $term = get_option( 'stdf_term_condition' );
						echo nl2br( esc_html( $term ) ); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
