<?php
$cp_view_invoice = CPLINK::get_user_meta('cp_view_invoice');
if( !$cp_view_invoice )
    exit;

require_once( CPLINK_PLUGIN_DIR . 'templates/woocommerce/sage/invoices/invoice-search.php' );
$my_invoices = $invoices;
echo '<table class="sage-invoices-table sage-table woocommerce-orders-table my_account_orders">';
echo '<thead><tr><th><span class="nobr">Invoice #</span></th><th><span class="nobr">Sales Order #</span></th><th><span class="nobr">Date</span></th>';
echo '<th><span class="nobr">PO #</span></th><th><span class="nobr">Tracking</span></th><th><span class="nobr">Balance</span></th>';
echo '<th><span class="nobr">Total</span></th><th><span class="nobr">Actions</span></th></tr></thead>';

Global $cp_modules_cf;
/*$scopConfig = get_option(CPLINK::get_settings_name('modules'), true);*/
/*i_print(CPLINK::$scopConfig);
i_print(CPLINK::$modulesConfig);*/


foreach ($my_invoices as $my_invoice){
    $invoice_id = $my_invoice['id'];
    $my_invoice_no = $my_invoice['invoice_no'];
    $sales_order_no = $my_invoice['sales_order_no'];
    $invoice_date = $my_invoice['invoice_date'];
    $customer_po_no = $my_invoice['customer_po_no'];
    $header_seq_no = $my_invoice['header_seq_no'];
    $balance = $my_invoice['balance'];
    $total = $my_invoice['total'];
    $my_invoice_line = CPLINK::get_sage_invoice_line($my_invoice_no);
    $my_invoice_trackings = CPLINK::get_sage_invoice_trackings($my_invoice_no, $header_seq_no);
    $invoice_view_url = wc_get_endpoint_url( 'invoice', $invoice_id, wc_get_page_permalink( 'myaccount' ) );

    $order_view_url = ''; $order_again_url = '';
    $web_sales_order_no = CPLINK::get_web_order_id($sales_order_no);

    if( $web_sales_order_no ){
        $order = wc_get_order( $web_sales_order_no );
        if( $order ) {
            $order_view_url = $order->get_view_order_url();
            $order_again_url = CPLINK::order_again_link($order);
        }
    } else {
        $order_view_url = wc_get_endpoint_url( 'view-order', $sales_order_no, wc_get_page_permalink( 'myaccount' ) ).'?sales_order';
    }

    //i_print($sales_order_line);

    echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order cplink_tr">';

    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">';
    echo '<a href="'.$invoice_view_url.'" class=""> '.$my_invoice_no.'</a>';
    echo '</td>';

    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">';
    echo '<a href="'.$order_view_url.'" class=""> '.$sales_order_no.'</a>';
    echo '</td>';

    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">';
    echo '<time datetime="'.date( 'c', strtotime( $invoice_date )).'">'.date( 'm/d/Y', strtotime( $invoice_date )).'</time>';
    echo '</td>'; //2021-12-21T16:43:51+00:00

    echo '<td class="po_td">';
    echo ''.$customer_po_no.'';
    echo '</td>';

    echo '<td class="invoices_tracking_td">';
    if( count( $my_invoice_trackings ) ){
        foreach ($my_invoice_trackings as $tracking){
            echo '<div>'.$tracking['tracking_id'].'</div>';
        }
    }
    echo '</td>';

    echo '<td>';
    echo ''.wc_price($balance).'';
    echo '</td>';

    echo '<td>';
    echo ''.wc_price($total).'';
    echo '</td>';

    echo '<td class="woocommerce-orders-table__cell woocommerce-invoices-table__cell-invoice-actions" data-title="Actions">';
    echo '<a href="'.$invoice_view_url.'" class="woocommerce-button view cplink_toggle_btn">'.__( 'View', 'woocommerce' ).'</a>';

    if ( $order_again_url && $cp_modules_cf['invoices_allow_reorder'] ) {
        echo '<span class="i_separator">|</span>';
        echo '<a href="'.$order_again_url.'" class="woocommerce-button order-again no-print">'.__( 'Reorder', 'woocommerce' ).'</a>';
    }

    echo '</td>';

    echo '</tr>';
}

echo '</table>';
?>

