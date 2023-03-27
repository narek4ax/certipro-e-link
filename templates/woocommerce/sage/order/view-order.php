<?php
/**
 * View Order
 *
 * Shows the details of a particular order on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/view-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="row view_order_top_div cplink_print_area">
    <div class="large-7 col">
        <p>
            <?php /*
            $date_created = date( 'm/d/Y', strtotime( $order->get_date_created() ));
            printf(
                esc_html__( 'Web Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
                '<strong class="order-number">' . $order->get_order_number() . '</strong>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                '<strong class="order-date">' . $date_created . '</strong>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                '<strong class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ); */
            ?>
        </p>
    </div>
    <div class="large-5 col view_order_actions_col">
        <a href="#" class="button cplink_print_this no-print" data-print_el=".cplink_print_area"><?php _e('Print', CPLINK_NAME); ?></a>
    </div>
</div>

<?php
$sales_order = array();
$sales_order_lines = array();
if( $cp_sales_order_number ) {
    wc_get_template( 'sage/order/sales-order-details.php', array( 'cp_sales_order_number' => $cp_sales_order_number ) );
    return;
}
?>
