<div id="sorders-search-block" class="elink-forms">
    <h3>Search</h3>
    
    <?php
    $sales_order_search_fields = array(
        'sales_order_no' => array(
            'name' => 'Sales Order Number',
            'type' => 'text'
        ),
        'web_sales_order_no' => array(
            'name' => 'Web Order Number',
            'type' => 'text'
        ),
        'customer_po_no' => array(
            'name' => 'PO Number',
            'type' => 'text'
        )
    );
    ?>
    
    <form method="GET" action="" id="order-block-search-form">
        <input type="hidden" name="sales_order_action" value="search">
        <?php
        foreach ($sales_order_search_fields as $s_o_sf_key => $sales_order_search_field) { 
            echo '<div>';
            $s_val = ( isset($_GET[$s_o_sf_key]) )?$_GET[$s_o_sf_key]:'';
            echo '<input type="' . $sales_order_search_field['type'] . '" id="osf_' . $s_o_sf_key . '" value="'.$s_val.'" name="' . $s_o_sf_key . '" placeholder="' . $sales_order_search_field['name'] . '">';
            echo '</div>';
        }
        ?>
                <div class="search">
                    <input type="submit" value="Search" class="sage_order_search_button">
                </div>
    </form>
</div>