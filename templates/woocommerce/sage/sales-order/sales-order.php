<?php
global $cp_modules_cf;
$sales_order_allow_reorder = CPLINK::isset_return($cp_modules_cf, 'sales_order_allow_reorder');

echo '<table id="" class="sage-table sage-orders-table woocommerce-orders-table my_account_orders cplink_print_area">';
//echo '<table id="sage-orders-table" class="dataTable sage-table sage-orders-table woocommerce-orders-table my_account_orders">';
echo '<thead><tr><th><span class="nobr">Sales Order #</span></th><th><span class="nobr">Web Order #</span></th><th><span class="nobr">Date</span></th>';
echo '<th><span class="nobr">PO #</span></th><th><span class="nobr">Total</span></th>';
echo '<th><span class="nobr">Invoice(s)</span></th><th><span class="nobr">Actions</span></th></tr></thead>';

foreach ($sales_orders as $sales_order){
    $sales_order_no = $sales_order['sales_order_no']; //i_print($sales_order);
    $web_sales_order_no = $sales_order['web_sales_order_no'];
    $customer_po_no = $sales_order['customer_po_no'];
    $sales_order_line = CPLINK::get_sage_order_line($sales_order_no);
    $sales_order_invoices = CPLINK::get_order_invoices($sales_order_no);
    $order_invoices_html = '';
    if( $sales_order_invoices ){
        foreach ($sales_order_invoices as $invoice) {
            $invoice_id = $invoice['id'];
            $my_invoice_no = $invoice['invoice_no'];
            $invoice_view_url = wc_get_endpoint_url( 'invoice', $invoice_id, wc_get_page_permalink( 'myaccount' ) );
            $order_invoices_html .= '<span><a href="'.$invoice_view_url.'" class=""> '.$my_invoice_no.'</a></span>';
        }
    }
    //i_print($sales_order_invoices);
    $order_again_url = '';
    $order_view_url = wc_get_endpoint_url( 'view-order', $sales_order_no, wc_get_page_permalink( 'myaccount' ) ).'?sales_order';

    if( $web_sales_order_no ){
        $order = wc_get_order( $web_sales_order_no );
        if( $order ) {
            $order_view_url = $order->get_view_order_url();
            $order_again_url = CPLINK::order_again_link($order);
        }
    }
    //i_print($sales_order_line);

    echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order cplink_tr">';

    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">';
    echo '<a href="'.$order_view_url.'" class="woocommerce-button view cplink_toggle_btn">'.$sales_order_no.'</a>';
    echo '</td>';

    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">';
    echo ''.$web_sales_order_no.'';
    echo '</td>';

    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">';
    echo '<time datetime="2021-12-21T16:43:51+00:00">'.date( 'm/d/Y', strtotime( $sales_order['order_date'] )).'</time>';
    echo '</td>';

    echo '<td class="po_td">';
    echo ''.$customer_po_no.'';
    echo '</td>';

    echo '<td>';
    echo ''.wc_price($sales_order['total']).'';
    echo '</td>';

    echo '<td class="invoices_td">';
    echo ''.$order_invoices_html.'';
    echo '</td>';

    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">';
    if( $order_view_url )
        echo '<a href="'.$order_view_url.'" class="woocommerce-button view cplink_toggle_btn">'.__( 'View', 'woocommerce' ).'</a>';
    if ( $order_again_url && $sales_order_allow_reorder ) {
        echo '<span class="i_separator">|</span>';
        echo '<a href="'.$order_again_url.'" class="woocommerce-button order-again no-print">'.__( 'Reorder', 'woocommerce' ).'</a>';
    }
    echo '</td>';

    echo '</tr>';

    /*echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order cplink_tr cplink_toggle_el" ';
    echo 'data-cplink_el="'.$sales_order_no.'" style="display: none;">';

    echo '<td colspan="5">';
    echo '<table>';
    echo '<tr><td><b>Sage#:</b></td><td>'.$sales_order_no.'</td></tr>';

    foreach ($sales_order_fields as $sales_order_field => $sales_order_field_name){
        $field_val = $sales_order[$sales_order_field];
        echo '<tr><td><b>'.$sales_order_field_name.':</b></td><td>'.$field_val.'</td></tr>';
    }

    foreach ($sales_order_line as $order_line){
        foreach ($order_line_fields as $order_line_field => $order_line_field_name){
            $field_val = $order_line[$order_line_field];
            if( in_array($order_line_field, $price_fields) )
                $field_val = wc_price( $field_val );
            echo '<tr><td><b>'.$order_line_field_name.':</b></td><td>'.$field_val.'</td></tr>';
        }
    }
    echo '</table>';
    echo '</td>';

    echo '</tr>';*/
}

echo '</table>';