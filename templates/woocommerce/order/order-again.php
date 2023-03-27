<?php
/**
 * Order again button
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-again.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php

if( $order ) {
    $order_view_url = $order->get_view_order_url();
    $order_again_url = CPLINK::order_again_link($order);

    if ( $order_again_url ) {
        echo '<a href="'.$order_again_url.'" class="woocommerce-button button order-again no-print">'.__( 'Reorder', 'woocommerce' ).'</a>';
    }
}
?>