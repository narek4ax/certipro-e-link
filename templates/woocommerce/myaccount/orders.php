<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>
<div class="cplink_orders_div">
    <?php
    $all_web_sales_order_nos = CPLINK::get_sage_orders(1, -1, 'web_sales_order_no', true);

    if ( $has_orders && $current_page < 2 ){
        //i_print($customer_orders);
        echo '<h2>'.__('Pending Orders', CPLINK_NAME).'</h2>';
        echo '<table class="sage-orders-table sage-table woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
        echo '<thead><tr><th><span class="nobr">Web Order #</span></th><th><span class="nobr">Date</span></th><th><span class="nobr">Ship To</span></th>';
        echo '<th><span class="nobr">Total</span></th><th><span class="nobr">Actions</span></th></tr></thead>';
        if ( $customer_orders && $customer_orders->total ){
            foreach ($customer_orders->orders as $customer_order){
                $order = wc_get_order( $customer_order ); //i_print($order);
                $order_again_url = CPLINK::order_again_link($order);
                $order_id = $order->get_id();
                $date_created = date( 'm/d/Y', strtotime( $order->get_date_created() ));

                $cp_sales_order_hide = false;

                $cp_sales_order_number = $order->get_meta('cp_sales_order_number'); //$cp_sales_order_number = get_post_meta($order_id, 'cp_sales_order_number', true);

                $cp_sales_order_status = CPLINK::order_queue_value($order_id, 'status');
                $cp_sales_order_active = CPLINK::order_queue_value($order_id, 'active');
                /*if( $order_id == 3696 ){
                    i_print($all_web_sales_order_nos); i_print($cp_sales_order_status); i_print($cp_sales_order_active);
                }*/
                //if( in_array($order_id, $all_web_sales_order_nos) ) //GX

                if( !$cp_sales_order_number ) {
                    if( $cp_sales_order_status == 1 || !$cp_sales_order_active ) {
                            $cp_sales_order_hide = true;
                    }
                } else {
                    //$cp_sales_order_hide = true;
                }

                if( $cp_sales_order_active === '0' )
                    $cp_sales_order_hide = true;

                /*if( $cp_sales_order_hide && $cp_sales_order_status == 1 && !in_array($order_id, $all_web_sales_order_nos) )
                    $cp_sales_order_hide = false;*/

                if( $cp_sales_order_hide )
                    continue;

                /*$customer_id = $order->get_customer_id();
                $user = $order->get_user();*/
                $ship_to_name = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();

                $order_n = $order->get_order_number();
                $item_count = $order->get_item_count() - $order->get_item_count_refunded();
                $order_view_url = $order->get_view_order_url();
                $order_status = wc_get_order_status_name( $order->get_status() );
                //i_print($customer_order);

                echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order cplink_tr">';

                echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">';
                echo '<a href="'.$order_view_url.'">'. $order_n.'</a>'; //esc_html( _x( '#', 'hash before order number', 'woocommerce' ) .'')
                echo '</td>';

                echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">';
                echo '<time datetime="'.esc_attr( $order->get_date_created()->date( 'c' ) ).'">'.esc_html( $date_created ).'</time>';
                echo '</td>';

                echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-ship_to" data-title="Ship To">';
                echo ''.$ship_to_name.'';
                echo '</td>';
                /*echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="Status">';
                echo ''.$order_status.'';
                echo '</td>';*/

                echo '<td>';
                //echo ''.wp_kses_post( sprintf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce' ), $order->get_formatted_order_total(), $item_count ) ).'';
                echo ''.wp_kses_post( $order->get_formatted_order_total()).'';
                echo '</td>';

                echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">';
                echo '<a href="'.$order_view_url.'" class="woocommerce-button view" >View</a>';

                if ( $order_again_url ) {
                    echo '<span class="i_separator">|</span>';
                    echo '<a href="'.$order_again_url.'" class="woocommerce-button order-again no-print">'.__( 'Reorder', 'woocommerce' ).'</a>';
                }
                echo '</td>';

                echo '</tr>';
            }
        }
        echo '</table>';
    }

    ?>

    <?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

    <?php
    /*
    <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
        <a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Browse products', 'woocommerce' ); ?></a>
        <?php esc_html_e( 'No order has been made yet.', 'woocommerce' ); ?>
    </div>
    */ ?>
</div>
<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
