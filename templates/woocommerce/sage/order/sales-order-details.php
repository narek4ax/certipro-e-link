<?php
/**
 * Order details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/sage/order/sales-order-details.php.
 *
 */

$sales_order = CPLINK::getSalesOrder($cp_sales_order_number); //i_print($sales_order);
$sales_order_lines = CPLINK::getSalesOrderLines($cp_sales_order_number);
//i_print($sales_order);
$bill_ship_to = array(
    'name', 'address1', 'address2', 'zipcode', 'city', 'state', 'code'
);

function cp_field_typed_val($f_val, $type)
{
    switch ($type) {
        case 'price':
            $f_val = wc_price($f_val);
            break;
        case 'int':
            $f_val = intval($f_val);
            break;
        case 'text':
        default:
            $f_val;
    }
    return $f_val;
}

?>
    <section class="woocommerce-order-details cplink_print_area">

        <h2 class="woocommerce-order-details__title"><?php esc_html_e('Order details', 'woocommerce'); ?></h2>

        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details sage-table">

            <thead>
            <tr>
                <?php
                $tbl_data = array(
                    'item_code' => array('label' => 'Code', 'type' => 'text'),
                    'item_code_desc' => array('label' => 'Name', 'type' => 'text'),
                    'unit_price' => array('label' => 'Price', 'type' => 'price'),
                    'quantity' => array('label' => 'Qty', 'type' => 'int'),
                    'back_quantity' => array('label' => 'Back Qty', 'type' => 'int'),
                    'extension_amt' => array('label' => 'Subtotal', 'type' => 'price'),
                );
                foreach ($tbl_data as $th_key => $th_data) {
                    echo '<th class="woocommerce-table__product-' . $th_key . ' product-' . $th_key . '">' . esc_html($th_data['label'], 'woocommerce') . '</th>';
                }
                ?>
            </tr>
            </thead>

            <tbody>
            <?php
            //do_action('woocommerce_order_details_before_order_table_items', $order);
            if ($cp_sales_order_number) {
                if (count($sales_order_lines)) {
                    foreach ($sales_order_lines as $sales_order_line) {
                        echo '<tr class="woocommerce-table__line-item order_item">';
                        foreach ($tbl_data as $f_key => $f_data) {
                            $f_val = '';
                            if (isset($sales_order_line[$f_key])) {
                                $f_val = $sales_order_line[$f_key];
                                $f_val = cp_field_typed_val($f_val, $f_data['type']);
                            }

                            echo '<td class="woocommerce-table__product-' . $f_key . ' product-' . $f_key . '">' . $f_val . '</td>';
                        }
                        echo '</tr>';
                    }
                }
            } else {

            }


            //do_action('woocommerce_order_details_after_order_table_items', $order);
            ?>
            </tbody>

            <tfoot>
            <?php

            $tbl_foot_data = array(
                'taxable_amount' => array('label' => 'Taxable Amount', 'type' => 'price'),
                'nontaxable_amount' => array('label' => 'Non-Taxable Amount', 'type' => 'price'),
                'sales_tax_amount' => array('label' => 'Sales Tax', 'type' => 'price'),
                'freight_amount' => array('label' => 'Freight', 'type' => 'price'),
                'total' => array('label' => 'Grand Total', 'type' => 'price'),
            );
            foreach ($tbl_foot_data as $f_key => $f_data) {
                $f_val = '';
                if (isset($sales_order[$f_key])) {
                    $f_val = $sales_order[$f_key];
                    $f_val = cp_field_typed_val($f_val, $f_data['type']);
                }
                echo '<tr>';
                echo '<th colspan="5" class="mark" scope="row">' . $f_data['label'] . '</th>';
                echo '<td>' . $f_val . '</td>';
                echo '</tr>';
            }
            ?>
            <?php
            /*$order_payment_data = array(
                'subtotal' => array(
                    'label' => 'Subtotal',
                    'value' => wc_price($order_subtotal)
                ),
                'payment_method' => array(
                    'label' => 'Payment method',
                    'value' => 'Paypal'
                ),
                'total' => array(
                    'label' => 'Total',
                    'value' => wc_price($sales_order['total'])
                ),
            );
            foreach ($order_payment_data as $key => $total) {
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html($total['label']); ?></th>
                    <td><?php echo ('payment_method' === $key) ? esc_html($total['value']) : wp_kses_post($total['value']); ?></td>
                </tr>
                <?php
            }
            ?>
            <?php if ($order && $order->get_customer_note()) : ?>
                <tr>
                    <th><?php esc_html_e('Note:', 'woocommerce'); ?></th>
                    <td><?php echo wp_kses_post(nl2br(wptexturize($order->get_customer_note()))); ?></td>
                </tr>
            <?php endif;*/ ?>
            </tfoot>
        </table>

        <?php //do_action('woocommerce_order_details_after_order_table', $order); ?>
    </section>
    <div class="sage_additional_info cplink_print_area">
        <section class="woocommerce-customer-details">
            <div class="row">
                <div class="col large-6">
                    <div class="col-inner">
                        <h3><?php _e('Order Information', CPLINK_NAME); ?></h3>
                        <table class="sage-table">
                            <tbody>
                            <?php
                            //i_print($sales_order);
                            $cp_order_data = array(
                                'Sales Order #' => $cp_sales_order_number,
                                'Order Date' => date( 'm/d/Y', strtotime( CPLINK::isset_return($sales_order, 'order_date') )),
                                'Customer Number' => CPLINK::isset_return($sales_order, 'customer_no'),
                                'Customer PO Number' => CPLINK::isset_return($sales_order, 'customer_po_no'),
                            );
                            foreach ($cp_order_data as $cp_order_data_name => $cp_order_data_val) {
                                echo '<tr>';
                                echo '<th scope="row">' . __($cp_order_data_name, CPLINK_NAME) . '</th>';
                                echo '<td>' . $cp_order_data_val . '</td>';
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
                                        if (isset($sales_order['billto_' . $bill]) && !empty($sales_order['billto_' . $bill]))
                                            echo $sales_order['billto_' . $bill] . '<br>';
                                    } ?>
                                </address>
                            </div><!-- /.col-1 -->

                            <div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2">
                                <h4 class="woocommerce-column__title"><?php _e('Shipping address', CPLINK_NAME); ?></h4>
                                <address>

                                    <?php
                                    foreach ($bill_ship_to as $ship) {
                                        if (isset($sales_order['shipto_' . $ship]) && !empty($sales_order['shipto_' . $ship]))
                                            echo $sales_order['shipto_' . $ship] . '<br>';
                                    } ?>
                                </address>
                            </div><!-- /.col-2 -->

                        </section><!-- /.col2-set -->
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="sage_order_invoices_info cplink_print_area">

        <section class="woocommerce-customer-details">
            <div class="row">
                <div class="col large-12">
                    <div class="col-inner">
                        <?php
                        $sales_order_invoices = CPLINK::get_order_invoices($cp_sales_order_number);
                        //i_print($sales_order_invoices);
                        if( $sales_order_invoices ){
                            echo '<h3>'.__('Invoices', CPLINK_NAME).'</h3>';
                            echo '<table class="sage-invoices-table sage-table woocommerce-orders-table my_account_order_invoices"><thead><tr>';
                            echo '<th class="col inv-date">Invoice Date</th><th class="col inv-number">Invoice Number</th><th class="col tracking">Tracking Number</th>';
                            echo '<th class="col inv-total">Invoice Total</th><th class="col actions"></th></tr></thead><tbody>';
                            foreach ($sales_order_invoices as $my_invoice) {
                                $invoice_id = $my_invoice['id'];
                                $my_invoice_no = $my_invoice['invoice_no'];
                                $sales_order_no = $my_invoice['sales_order_no'];
                                $invoice_date = $my_invoice['invoice_date'];
                                $customer_po_no = $my_invoice['customer_po_no'];
                                $header_seq_no = $my_invoice['header_seq_no'];
                                $balance = $my_invoice['balance'];
                                $total = $my_invoice['total'];

                                $invoice_view_url = wc_get_endpoint_url( 'invoice', $invoice_id, wc_get_page_permalink( 'myaccount' ) );
                                $my_invoice_trackings = CPLINK::get_sage_invoice_trackings($my_invoice_no, $header_seq_no);

                                echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order cplink_tr">';

                                echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">';
                                echo '<time datetime="'.date( 'c', strtotime( $invoice_date )).'">'.date( 'm/d/Y', strtotime( $invoice_date )).'</time>';
                                echo '</td>'; //2021-12-21T16:43:51+00:00

                                echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">';
                                echo '<a href="'.$invoice_view_url.'" class=""> '.$my_invoice_no.'</a>';
                                echo '</td>';


                                echo '<td class="invoices_tracking_td">';
                                if( count( $my_invoice_trackings ) ){
                                    foreach ($my_invoice_trackings as $tracking){
                                        echo '<div> <a href="https://iship.com/trackit/track.aspx?T=1&Track='.$tracking['tracking_id'].'" target="_blank">'.$tracking['tracking_id'].'</a> </div>';
                                    }
                                }
                                echo '</td>';

                                echo '<td>';
                                echo ''.wc_price($total).'';
                                echo '</td>';

                                echo '<td class="woocommerce-orders-table__cell woocommerce-invoices-table__cell-invoice-actions" data-title="Actions">';
                                echo '<a href="'.$invoice_view_url.'" class="woocommerce-button view cplink_toggle_btn">'.__( 'View', 'woocommerce' ).'</a>';
                                echo '</td>';

                                echo '</tr>';

                            }
                            echo '</tbody></table>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
<?php
/**
 * Action hook fired after the order details.
 *
 * @param WC_Order $order Order data.
 * @since 4.4.0
 */
//do_action('woocommerce_after_order_details', $order);

