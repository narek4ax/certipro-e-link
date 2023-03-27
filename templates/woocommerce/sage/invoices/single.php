<?php
$cp_view_invoice = CPLINK::get_user_meta('cp_view_invoice');
if( !$cp_view_invoice )
    exit;

global $cp_scope_cf, $cp_modules_cf;
//$invoice_id = get_query_var('invoices');
//i_print($scopConfig);

$my_invoice = CPLINK::get_sage_invoice($invoice_id);

$bill_ship_to = array(
    'name', 'address1', 'address2', 'zipcode', 'city', 'state', 'code'
);

if (count($my_invoice)) {
    $invoice_id = $my_invoice['id'];
    $invoice_no = $my_invoice['invoice_no'];
    $header_seq_no = $my_invoice['header_seq_no'];
    $sales_order_no = $my_invoice['sales_order_no'];
    $invoice_date = $my_invoice['invoice_date'];
    $customer_po_no = $my_invoice['customer_po_no'];
    $total = $my_invoice['total'];
    $invoice_view_url = wc_get_endpoint_url('invoices', $invoice_id, wc_get_page_permalink('myaccount'));


    $order_view_url = '';
    $order_again_url = '';
    $web_sales_order_no = CPLINK::get_web_order_id($sales_order_no);

    if ($web_sales_order_no) {
        $order = wc_get_order($web_sales_order_no);
        if ($order) {
            $order_view_url = $order->get_view_order_url();
            $order_again_url = CPLINK::order_again_link($order);
        }
    } else {
        $order_view_url = wc_get_endpoint_url('view-order', $sales_order_no, wc_get_page_permalink('myaccount')) . '?sales_order';
    }

    $invoice_lines = CPLINK::get_sage_invoice_line($invoice_no);
    //i_print($invoice_line);
    //i_print($my_invoice);


    echo '<div class="row view_order_top_div">';
    echo '<div class="large-7 col"> <h2>Items Ordered</h2> </div>';
    echo '<div class="large-5 col view_order_actions_col"> <a href="#" class="button cplink_print_this no-print" data-print_el=".cplink_print_area">Print</a>';
    if ($order_again_url && $cp_modules_cf['invoices_allow_reorder']) {
        echo '<a href="' . $order_again_url . '" class="woocommerce-button button order-again no-print">' . __('Reorder', 'woocommerce') . '</a>';
    }
    echo '</div>';
    echo '</div>';

    echo '<table class="sage-invoice-table sage-table woocommerce-orders-table my_account_orders">'; //
    echo '<thead><tr><th><span class="nobr">Code</span></th><th><span class="nobr">Name</span></th><th><span class="nobr">Serial Numbers</span></th>';
    echo '<th><span class="nobr">Price</span></th><th><span class="nobr">Qty</span></th><th><span class="nobr">Subtotal</span></th>';
    echo '</thead>';


    echo '<tbody>';
    if (count($invoice_lines))
        foreach ($invoice_lines as $invoice_line) {
            $line_header_seq_no = $invoice_line['header_seq_no'];
            $line_key = $invoice_line['line_key'];
            $serial_numbers = CPLINK::get_sage_invoice_line($invoice_no, $line_header_seq_no, $line_key, 'lot_serial_number');
            $serial_numbers_txt = CPLINK::isset_return($serial_numbers, 'lot_serial_number', '');
            echo '<tr class="">';
            echo '<td>' . $invoice_line['item_code'] . '</td>';
            echo '<td>' . $invoice_line['item_code_desc'] . '</td>';
            echo '<td>' . $serial_numbers_txt . '</td>';
            echo '<td>' . wc_price($invoice_line['unit_price']) . '</td>';
            echo '<td>' . intval($invoice_line['quantity']) . '</td>';
            echo '<td>' . wc_price($invoice_line['extension_amt']) . '</td>';
            echo '</tr>';
        }
    echo '</tbody>';

    echo '<tfoot>';

    $footer_data = array(
        'Taxable Amount' => wc_price($my_invoice['taxable_amount']),
        'Non-Taxable Amount' => wc_price($my_invoice['nontaxable_amount']),
        'Sales Tax' => wc_price($my_invoice['sales_tax_amount']),
        'Freight' => wc_price($my_invoice['freight_amount']),
        'Balance' => wc_price($my_invoice['balance']),
        '<b>Grand Total</b>' => wc_price($total),
    );
    foreach ($footer_data as $footer_item_name => $footer_item_val) {
        echo '<tr class="">';
        echo '<td colspan="4"></td>';
        echo '<td>' . $footer_item_name . '</td>';
        echo '<td>' . $footer_item_val . '</td>';
        echo '</tr>';
    }
    echo '</tfoot>';

    echo '</table>';

    //echo '<marquee direction="right" style="cursor: default;"><h4>To be continued <i class="fa fa-arrow-right"></i> </h4></marquee>';
    ?>
    <div class="sage_additional_info">
        <h2><?php _e('Invoice Information', CPLINK_NAME); ?></h2>
        <section class="woocommerce-customer-details">
            <div class="row">
                <div class="col large-6">
                    <div class="col-inner">
                        <h4><?php _e('Additional', CPLINK_NAME); ?></h4>
                        <table class="sage-table">
                            <tbody>
                            <?php
                            $inv_order_val = $my_invoice['sales_order_no'];
                            if($order_view_url)
                                $inv_order_val = '<a href="'.$order_view_url.'" class=""> '.$my_invoice['sales_order_no'].'</a>';
                            $cp_invoice_data = array(
                                'Invoice Number' => $invoice_no,
                                'Invoice Date' => $my_invoice['invoice_date'],
                                'Order Number' => $inv_order_val,
                                'Customer Number' => $my_invoice['customer_no'],
                                'Customer PO Number' => $my_invoice['customer_po_no'],
                            );
                            foreach ($cp_invoice_data as $cp_invoice_data_name => $cp_invoice_data_val) {
                                echo '<tr>';
                                echo '<th scope="row">' . __($cp_invoice_data_name, CPLINK_NAME) . ':</th>';
                                echo '<td>' . $cp_invoice_data_val . '</td>';
                                echo '</tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col large-6">
                    <div class="col-inner">
                        <section
                                class="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses">
                            <div class="woocommerce-column woocommerce-column--1 woocommerce-column--billing-address col-1">
                                <h4 class="woocommerce-column__title"><?php _e('Billing address', CPLINK_NAME); ?></h4>
                                <address>
                                    <?php
                                    foreach ($bill_ship_to as $bill) {
                                        if (isset($my_invoice['billto_' . $bill]) && !empty($my_invoice['billto_' . $bill]))
                                            echo $my_invoice['billto_' . $bill] . '<br>';
                                    } ?>
                                </address>
                            </div><!-- /.col-1 -->

                            <div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2">
                                <h4 class="woocommerce-column__title"><?php _e('Shipping address', CPLINK_NAME); ?></h4>
                                <address>

                                    <?php
                                    foreach ($bill_ship_to as $ship) {
                                        if (isset($my_invoice['shipto_' . $ship]) && !empty($my_invoice['shipto_' . $ship]))
                                            echo $my_invoice['shipto_' . $ship] . '<br>';
                                    } ?>
                                </address>
                            </div><!-- /.col-2 -->

                        </section><!-- /.col2-set -->
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="sage_invoice_trackings_div">

        <?php
        $my_invoice_trackings = CPLINK::get_sage_invoice_trackings($invoice_no, $header_seq_no); //i_print($my_invoice_trackings);

        if( count( $my_invoice_trackings ) ){
            ?>
            <h2><?php _e('Shipments', CPLINK_NAME); ?></h2>
            <section class="woocommerce-customer-details">
                <div class="row">
                    <div class="col large-12">
                        <div class="col-inner">
                            <?php
                            $view_data = array(
                                'tracking_id' => array(
                                    'title' => 'Tracking #'
                                ),
                                'comment' => array(
                                    'title' => 'Comment'
                                ),
                            );
                            echo '<table class="sage-table"><thead><tr>';
                            foreach ($view_data as $view_item_key => $view_item){
                                echo '<th><span class="nobr '.$view_item_key.'">'.$view_item['title'].'</span></th>';
                            }
                            echo '</tr></thead>';
                            echo '<tbody>';
                            foreach ($my_invoice_trackings as $tracking){
                                echo '<tr class="">';
                                foreach ($view_data as $view_item_key => $view_item){
                                    if( $view_item_key == 'tracking_id' ){
                                        $v_txt = '<a href="https://iship.com/trackit/track.aspx?T=1&Track='.$tracking[$view_item_key].'" target="_blank">'.$tracking[$view_item_key].'</a>';
                                    } else {
                                        $v_txt = $tracking[$view_item_key];
                                    }

                                    echo '<td><span>'.$v_txt.'</span></td>';
                                }
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                            ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php
        }
        ?>
    </div>
    <?php
}

?>

