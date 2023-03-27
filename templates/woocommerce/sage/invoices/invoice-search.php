<div id="search-invoices" class="elink-forms">
    <h3>Search</h3>
    <?php
    $search_fields = array(
        'invoice_no' => array(
            'name' => 'Invoice Number',
            'type' => 'text'
        ),
        'sales_order_no' => array(
            'name' => 'Sales Order Number',
            'type' => 'text'
        ),
        'customer_po_no' => array(
            'name' => 'PO Number',
            'type' => 'text'
        ),
        'item_code' => array(
            'name' => 'Item Code',
            'type' => 'text'
        ),
        'lot_serial_number' => array(
            'name' => 'Serial Number',
            'type' => 'text'
        ),
        'min_balance' => array(
            'name' => 'Balance Min',
            'type' => 'text'
        ),
        'max_balance' => array(
            'name' => 'Balance Max',
            'type' => 'text'
        ),
        'min_total' => array(
            'name' => 'Total Min',
            'type' => 'text'
        ),
        'max_total' => array(
            'name' => 'Total Max',
            'type' => 'text'
        ),
    );
    ?>
    <form action="" id="invoices-search-form">
        <input type="hidden" name="invoice_action" value="search">
        <?php
        foreach ($search_fields as $sf_key => $search_field) {
            echo '<div>';
            $s_val = ( isset($_GET[$sf_key]) )?$_GET[$sf_key]:'';
            echo '<input type="' . $search_field['type'] . '" id="sf_' . $sf_key . '" value="'.$s_val.'" name="' . $sf_key . '" placeholder="' . $search_field['name'] . '">';
            echo '</div>';
        }
        ?>
        <div class="invoices-search">
            <input type="submit" id="sort_invoices_order" value="Search">
        </div>
    </form>
</div>