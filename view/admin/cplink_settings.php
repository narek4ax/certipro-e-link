<?php
global $cplink_options;
global $CP_Sage;


$shipping_methods_array = array();

$shipping_methods = WC()->shipping->get_shipping_methods();

foreach($shipping_methods as $shipping_method){
    //i_print($shipping_method);
    if($shipping_method->id != 'usps_simple'){
        $shipping_methods_array[$shipping_method->id] = $shipping_method->method_title;
    }else{
        $usps_shipping_methods = array(
            't_express_mail', 't_priority_mail', 't_first_class',
            't_standard_post', 't_media_mail', 't_library_mail'
        );
        foreach ($usps_shipping_methods as $usps_shipping_method){
            if( isset($shipping_method->{$usps_shipping_method}) ){
                $usps_shipping_method_item = $shipping_method->{$usps_shipping_method};
                $shipping_methods_array['usps_simple_'.strtolower(str_replace(' ','_',$usps_shipping_method_item))] = $usps_shipping_method_item . ' ' .__('(USPS Simple)', CPLINK_NAME);
            }
        }
        /*$shipping_methods_array['usps_simple_'.strtolower(str_replace(' ','_',$shipping_method->t_express_mail))] = $shipping_method->t_express_mail . ' ' .__('(USPS Simple)',CPLINK_NAME);
        $shipping_methods_array['usps_simple_'.strtolower(str_replace(' ','_',$shipping_method->t_priority_mail))] = $shipping_method->t_priority_mail . ' ' .__('(USPS Simple)',CPLINK_NAME);
        $shipping_methods_array['usps_simple_'.strtolower(str_replace(' ','_',$shipping_method->t_first_class))] = $shipping_method->t_first_class . ' ' .__('(USPS Simple)',CPLINK_NAME);
        $shipping_methods_array['usps_simple_'.strtolower(str_replace(' ','_',$shipping_method->t_standard_post))] = $shipping_method->t_standard_post . ' ' .__('(USPS Simple)',CPLINK_NAME);
        $shipping_methods_array['usps_simple_'.strtolower(str_replace(' ','_',$shipping_method->t_media_mail))] = $shipping_method->t_media_mail . ' ' .__('(USPS Simple)',CPLINK_NAME);
        $shipping_methods_array['usps_simple_'.strtolower(str_replace(' ','_',$shipping_method->t_library_mail))] = $shipping_method->t_library_mail . ' ' .__('(USPS Simple)',CPLINK_NAME);*/
    }

    /*foreach($shipping_method->rates as $key=>$val)
        $rate_table[$key] = $val->label;*/
}
//i_print($shipping_methods_array);

// Prepare data for options values --
$sage_shipping_methods = array('' => '');
$sage_shipping_data = $CP_Sage->getShippingmethods( null, 0, -1, '', true);
if( !empty($sage_shipping_data) ){
    foreach ($sage_shipping_data as $sage_shipping_item){
        $sage_shipping_methods[$sage_shipping_item->shipping_code] = $sage_shipping_item->shipping_code.' - '.$sage_shipping_item->shipping_code_description;
    }
}

$shipping_methods_settings = array();
if( count($shipping_methods_array) )
    foreach ($shipping_methods_array as $shipping_method_key => $shipping_method){
        array_push($shipping_methods_settings,
            array(
                'title' => __($shipping_method, CPLINK_NAME),
                'subtitle' => '',
                'type' => 'selectbox',
                'id' => $shipping_method_key,
                'options' => $sage_shipping_methods,
                'value' => '',
                'default' => '',
                'multiple' => false,
                'description' => ''
            )
        );
    }

$shipping_methods_list = array(
    'cash_on_delivery' => 'Cash On Delivery',
    'u_s_postal_service' => 'U.S. POSTAL SERVICE',
    'shipping_product_line' => 'Shipping Product Line',
    'rate_times_weight' => 'Rate Times Weight',
    'flat_rate_base_on_amount' => 'Flat Rate Base on Amount',
    'ups_2nd_day_air' => 'UPS 2ND DAY AIR',
    'ups_next_day_air' => 'UPS NEXT DAY AIR',
);
$shipping_methods_html = '';
foreach ($shipping_methods_list as $shipping_method){
    $shipping_methods_html.= '<div>'.$shipping_method.'</div>';
}
$shipping_methods_html = '<div class="all_shipping_methods">'.$shipping_methods_html.'</div>';

$home_url = home_url(); //i_print($cplink_uploads);
$menu_page_url = menu_page_url(CPLINK_SETTINGS_LINK, false);

// Prepare data for options values --
$warehouses = array();
$warehouses_data = $CP_Sage->getWarehouses( null, 0, -1, '', true);
if( !empty($warehouses_data) ){
    foreach ($warehouses_data as $warehouse_item){
        $warehouses[$warehouse_item->warehouse_code] = $warehouse_item->warehouse_code.' - '.$warehouse_item->warehouse_description;
    }
}

$terms_code = array();
$terms_code_data = $CP_Sage->getTermsCodes( null, 0, -1, '', true);
if( !empty($terms_code_data) ){
    foreach ($terms_code_data as $terms_code_item){
        $terms_code[$terms_code_item->terms_code] = $terms_code_item->terms_code.' - '.$terms_code_item->terms_code_description;
    }

    /*array(
        '00' => '00 - No Terms',
        '01' => '01 - No Terms',
    )*/
}
$payment_types = array();
$payment_types_data = $CP_Sage->getPaymentTypes( null, 0, -1, '', true);
if( !empty($payment_types_data) ){
    foreach ($payment_types_data as $payment_types_item){
        if($payment_types_item->payment_method == "R"){
            $payment_types[$payment_types_item->payment_type] = $payment_types_item->payment_type.' - '.$payment_types_item->payment_description;
        }
    }

    /*array(
        '00' => '00 - No Terms',
        '01' => '01 - No Terms',
    )*/
}


$woo_active_payments = array();
$gateways = WC()->payment_gateways->get_available_payment_gateways();
if( $gateways ) {
    foreach( $gateways as $gateway ) {
        if( $gateway->enabled == 'yes' ) {
            $woo_active_payments[$gateway->id] = $gateway->title;
        }
    }
}
// -- Prepare data for options values



$tabs = apply_filters('cplink_option_tabs_filter',
    array(
        'general' => array(
            'title' => __('General', CPLINK_NAME),
            'id' => 'general'
        ),
        'modules' => array(
            'title' => __('Modules', CPLINK_NAME),
            'id' => 'modules'
        ),
        'shipping_methods' => array(
            'title' => __('Shipping Methods', CPLINK_NAME),
            'id' => 'shipping_methods'
        ),
    )
);
$sections = apply_filters('cplink_option_sections_filter',
    array(
        'general' => array(
            array(
                'title' => __('Connection Information', CPLINK_NAME),
                'id' => 'connection_information'
            ),
            array(
                'title' => __('Sage Default Settings', CPLINK_NAME),
                'id' => 'sage_default_settings'
            ),
            array(
                'title' => __('Payment Types', CPLINK_NAME),
                'id' => 'payment_types'
            ),
            array(
                'title' => __('Email Settings', CPLINK_NAME),
                'id' => 'email_settings'
            ),
            /*array(
                'title' => __( 'Colors', CPLINK_NAME ),
                'id'    => 'colors_settings'
            ),
            array(
                'title' => __( 'Short Codes', CPLINK_NAME ),
                'id'    => 'short_codes'
            )*/
        ),
        'modules' => array(
            array(
                'title' => __('Order Export', CPLINK_NAME),
                'id' => 'order_export'
            ),
            /*array(
                'title' => __('Existing User Option & Guest User Option', CPLINK_NAME),
                'id' => 'existing_and_guest_user_option'
            ),*/
            array(
                'title' => __('Warehousees', CPLINK_NAME),
                'id' => 'warehouses'
            ),
            array(
                'title' => __('Customers', CPLINK_NAME),
                'id' => 'customers'
            ),
            array(
                'title' => __('Products', CPLINK_NAME),
                'id' => 'products'
            ),
            array(
                'title' => __('Pricing', CPLINK_NAME),
                'id' => 'pricing'
            ),
            array(
                'title' => __('Sales Order', CPLINK_NAME),
                'id' => 'sales_order'
            ),
            array(
                'title' => __('Invoices', CPLINK_NAME),
                'id' => 'invoices'
            ),
            array(
                'title' => __('Shipping Methods', CPLINK_NAME),
                'id' => 'shipping_methods'
            ),
            array(
                'title' => __('Payment Types', CPLINK_NAME),
                'id' => 'payment_types'
            ),
            array(
                'title' => __('Terms Code', CPLINK_NAME),
                'id' => 'terms_code'
            ),
        ),
        'shipping_methods' => array(
                array(
                    'title' => __('Shipping Methods', CPLINK_NAME),
                    'id' => 'shipping_methods'
                ),
        )
    )
);

$options = apply_filters('cplink_options_filter',
    array(
        'general' => array(
            'connection_information' => array(
                array(
                    'title' => __('API Base URL', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'api_url',
                    'required' => true,
                    'value' => '',
                    'description' => __('Please ...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Image Path', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'image_path',
                    'required' => true, 
                    'value' => '',
                    'description' => __('Please ...', CPLINK_NAME)
                ),
                array(
                    'title' => __('API Connection Timeout', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'api_conn_timeout',
                    'value' => '',
                    'default' => '0',
                    'description' => __('Maximum time in seconds that request takes. Use 0 for no timeout', CPLINK_NAME)
                ),
                array(
                    'title' => __('Public Key', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'textarea',
                    'id' => 'public_key',
                    'default' => '',
                    'value' => '',
                    'description' => __('Please...', CPLINK_NAME),
                    'rows' => '5'
                ),
                array(
                    'title' => __('Private Key', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'textarea',
                    'id' => 'private_key',
                    'default' => '',
                    'value' => '',
                    'description' => __('Please...', CPLINK_NAME),
                    'rows' => '5'
                ),
            ),
            'sage_default_settings' => array(
                array(
                    'title' => __('Division No.', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'division_no',
                    'value' => '',
                    'default' => '00',
                    'description' => __('Maximum Length: 2 characters', CPLINK_NAME)
                ),
                array(
                    'title' => __('Terms Code', CPLINK_NAME), 
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'terms_code',
                    'options' => $terms_code,
                    'value' => '',
                    'default' => '00',
                    'multiple' => false,
                    'description' => __('This value will be set as terms code for new sage customer on order exporting if non empty value is specified', CPLINK_NAME)
                ),
                array(
                    'title' => __('Tax Schedule', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'tax_schedule',
                    'value' => '',
                    'default' => 'Default',
                    'description' => __('This value will be set as tax schedule for order to export if it\'s not set on related sage customer and its default shipping address', CPLINK_NAME)
                ),
            ),
            'payment_types' => array(
                array(
                    'title' => __('American Express (AE)', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'payment_ae',
                    'options' => $payment_types,
                    'value' => '',
                    'default' => 'web',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Visa (VI)', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'payment_vi',
                    'options' => $payment_types,
                    'value' => '',
                    'default' => 'web',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('MasterCard (MC)', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'payment_mc',
                    'options' => $payment_types,
                    'value' => '',
                    'default' => 'web',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Discover (DI)', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'payment_di',
                    'options' => $payment_types,
                    'value' => '',
                    'default' => 'web',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('JCB (JCB)', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'payment_jcb',
                    'options' => $payment_types,
                    'value' => '',
                    'default' => 'web',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Automated Clearing House (ACH)', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'payment_ach',
                    'options' => $payment_types,
                    'value' => '',
                    'default' => 'ach',
                    'description' => __('...', CPLINK_NAME)
                ),
            ),
            'email_settings' => array(
                array(
                    'title' => __('Email Address on Global API error', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'email',
                    'id' => 'error_email_addr',
                    'required' => true,
                    'validate' => true,      
                    'value' => '',
                    'description' => __('Commas can be used to separate multiple emails', CPLINK_NAME)
                ),
                
                array(
                    'title' => __('Product Import Error', CPLINK_NAME),
                    'subtitle' => '',
                    'type' => 'html_view',
                    'id' => 'product_import_error',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Email', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'email',
                    'id' => 'product_import_error_email',
                    'validate' => true,      
                    'value' => '',
                    'description' => __('Commas can be used to separate multiple emails', CPLINK_NAME)
                ),
                array(
                    'title' => __('Template', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'product_import_error_template',
                    'options' => array(
                        'default' => 'Default',
                        'new_pickup_order' => 'New Pickup Order',
                        'new_pickup_order_for_guest' => 'New Pickup Order For Guest'
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Send Attachment', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'product_import_error_send_attachment',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Customer Import Error', CPLINK_NAME),
                    'subtitle' => '',
                    'type' => 'html_view',
                    'id' => 'customer_import_error',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Email', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'email',
                    'id' => 'customer_import_error_email',
                    'validate' => true,      
                    'value' => '',
                    'description' => __('Commas can be used to separate multiple emails', CPLINK_NAME)
                ),
                array(
                    'title' => __('Template', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'customer_import_error_template',
                    'options' => array(
                        'default' => 'Default',
                        'new_pickup_order' => 'New Pickup Order',
                        'new_pickup_order_for_guest' => 'New Pickup Order For Guest'
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Send Attachment', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'customer_import_error_send_attachment',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Order Export Error', CPLINK_NAME),
                    'subtitle' => '',
                    'type' => 'html_view',
                    'id' => 'order_export_error',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Email', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'email',
                    'id' => 'order_export_error_email',
                    'validate' => true,      
                    'value' => '',
                    'description' => __('Commas can be used to separate multiple emails', CPLINK_NAME)
                ),
                array(
                    'title' => __('Template', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'order_export_error_template',
                    'options' => array(
                        'default' => 'Default',
                        'new_pickup_order' => 'New Pickup Order',
                        'new_pickup_order_for_guest' => 'New Pickup Order For Guest'
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Send Attachment', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'order_export_error_send_attachment',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPLINK_NAME)
                ),
            ),
            /*'colors_settings' => array(
                array(
                    'title'       => __( 'Color 1 Primary', CPLINK_NAME ),
                    'subtitle'    => __( '( default #a7a9ac )', CPLINK_NAME ),
                    'type'        => 'color_picker',
                    'id'          => 'color_1_primary',
                    'default'       => '#a7a9ac',
                    'description' => __( 'Color 1 Primary', CPLINK_NAME )
                ),
                array(
                    'title'       => __( 'Enable Exclusive code', CPLINK_NAME ),
                    'subtitle'    => 'Allow people only with Exclusive code',
                    'heading'     => '',
                    'type'        => 'checkbox',
                    'id'          => 'enable_exclusive_code',
                    'description' => __( 'Check to enable', CPLINK_NAME ),
                    'placeholder' => ''
                ),
                array(
                    'title'       => __( 'Site Description', CPLINK_NAME ),
                    'subtitle'    => 'Enter site Description here',
                    'heading'     => '',
                    'type'        => 'textarea_editor',
                    'id'          => 'site_description',
                    'description' => '',
                    'placeholder' => __( 'Enter site Description here', CPLINK_NAME )
                ),
                array(
                    'title'       => __( 'Start time', CPLINK_NAME ),
                    'subtitle'    => __( 'Set the Start time', CPLINK_NAME ),
                    'heading'     => '',
                    'type'        => 'date_picker',
                    'id'          => 'timer_start_time',
                    'description' => __( 'day-month-year', CPLINK_NAME ),
                    'placeholder' => 'day-month-year'
                ),
                array(
                    'title'       => __( 'Shop closed page', CPLINK_NAME ),
                    'subtitle'    => __( 'Set the page for Shop closed', CPLINK_NAME ),
                    'heading'     => '',
                    'type'        => 'post_selector',
                    'id'          => 'shop_closed_page',
                    'description' => __( 'Shop closed page', CPLINK_NAME ),
                    'placeholder' => ''
                ),
            ),
            'short_codes' => array(
                array(
                    'title'       => __( 'Show Timer', CPLINK_NAME ),
                    'subtitle'    => __( 'Past this shortcode where you want', CPLINK_NAME ),
                    'type'        => 'intro_view',
                    'id'          => 'some_shortcode',
                    'value'       => '[example_shortcode title="your title"]',
                    'description' => __( 'Show something where you want', CPLINK_NAME )
                ),
            ),*/
        ),
        'modules' => array(
            'order_export' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'order_export_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Default Order Status', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'id' => 'default_order_status',
                    'options' => array(
                        'N' => 'New',
                        'O' => 'Open',
                        'H' => 'Hold',
                    ),
                    'value' => '',
                    'default' => 'N',
                    'description' => __('...', CPLINK_NAME)
                ),
                array(
                    'title' => __('Comment Item Code', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'comment_item_code',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'default' => '/C',
                    'value' => '',
                    'description' => __('Enter Comment Item Code for creating new comment line for New order on export', CPLINK_NAME)
                ),
                array(
                    'title' => __('Set Sage Order Number', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'sage_order_number',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'options' => array(
                        '0' => 'No',
                        '1' => 'Yes',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('Set Yes if you want to send WordPress order number as a sage number', CPLINK_NAME)
                ),
                array(
                    'title' => __('Sage Order Number Prefix', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'order_number_prefix',
                    'value' => '',
                    'description' => __('', CPLINK_NAME),
                    'dependencies' => array(
                        'sage_order_number' => '0'
                    ),
                ),
                array(
                    'title' => __('Exportable Order Status', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'exportable_order_status',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'options' => array(
                        '0' => 'New',
                        '1' => 'Sent',
                        '2' => 'Error on Send',
                    ),
                    'value' => '',
                    'default' => '0',
                    'multiple' => true,
                    'description' => __('Orders with queue atatus above will be exported', CPLINK_NAME)
                ),
                array(
                    'title' => __('Max Exporting Attempts', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'max_exporting_attempts',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('Orders with exporting attempts count less than this value will be exported. 0 or empty to disable', CPLINK_NAME)
                ),
                array(
                    'title' => __('Create Sage Customer Addresses', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'create_sage_customer_addresses',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'options' => array(
                        '0' => 'No',
                        '1' => 'Yes',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __("Set Yes if you want to create new sage customer's addresses if address not exist in sage.", CPLINK_NAME)
                ),
                array(
                    'title' => __('Existing User Option', CPLINK_NAME),
                    'subtitle' => '',
                    'type' => 'html_view',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'id' => 'existing_user_option',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Use Default Customer', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'use_default_customer_for_existing_user',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('If set to No new Sage customer will be created every time on order export. If set to Yes customer data specified below will be used on order export.', CPLINK_NAME)
                ),
                array(
                    'title' => __('AR Division No.', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'ar_division_no_for_existing_user',
                    'value' => '',
                    'default' => '0',
                    'dependencies' => array(
                        'order_export_enabled' => '1',
                        'use_default_customer_for_existing_user' => '1'
                    ),
                    'description' => __('Maximum Length: 2 characters. This field will be used as default divisin number on order export.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Customer No.', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'customer_no_for_existing_user',
                    'value' => '',
                    'default' => '',
                    'dependencies' => array(
                        'order_export_enabled' => '1',
                        'use_default_customer_for_existing_user' => '1'
                    ),
                    'description' => __('Maximum Length: 20 characters. This field will be used as default divisin number on order export.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Create Sage Customer', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'create_sage_customer_for_existing_user',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'dependencies' => array(
                        'order_export_enabled' => '1',
                        'use_default_customer_for_existing_user' => '0'
                    ),
                    'description' => __('Set Yes if you want create new customer if order or customer sage data is not set.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Guest User Option', CPLINK_NAME),
                    'subtitle' => '',
                    'type' => 'html_view',
                    'id' => 'guest_user_option',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Use Default Customer', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'use_default_customer_for_guest_user',
                    'dependencies' => array(
                        'order_export_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('If set to No new Sage customer will be created every time on order export. If set to Yescustomer data specified below will be used on order export.', CPLINK_NAME)
                ),
                array(
                    'title' => __('AR Division No.', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'ar_division_no_for_guest_user',
                    'value' => '',
                    'default' => '0',
                    'dependencies' => array(
                        'order_export_enabled' => '1',
                        'use_default_customer_for_guest_user' => '1'
                    ),
                    'description' => __('Maximum Length: 2 characters. This field will be used as default divisin number on order export.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Customer No.', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'customer_no_for_guest_user',
                    'value' => '',
                    'default' => '',
                    'dependencies' => array(
                        'order_export_enabled' => '1',
                        'use_default_customer_for_guest_user' => '1'
                    ),
                    'description' => __('Maximum Length: 20 characters. This field will be used as default divisin number on order export.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Create Sage Customer', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'create_sage_customer_for_guest_user',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'dependencies' => array(
                        'order_export_enabled' => '1',
                        'use_default_customer_for_guest_user' => '0'
                    ),
                    'description' => __('Set Yes if you want create new customer if order or customer sage data is not set.', CPLINK_NAME)
                ),
            ),
            'warehouses' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'warehouses_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Default Warehouse', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'default_warehouse',
                    'dependencies' => array(
                        'warehouses_enabled' => '1'
                    ),
                    'depends_from' => 'warehouses_enabled',
                    'dependance_value' => '1',
                    'options' => $warehouses,
                    'value' => '',
                    'default' => '000 - Central Warehose',
                    'description' => __('This settings secifies whichuse use if Sage Inventory enabled and customer warehouse is empty or not in warehouse list.', CPLINK_NAME)
                ),
            ),
            'customers' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'customers_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                /*array(
                    'title' => __('Assign To Website', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'customers_assign_to_website',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'options' => array(
                        'main_website' => 'Main Website'
                    ),
                    'value' => '',
                    'default' => 'Main Website',
                    'description' => __('This setting specifies to which web site assign imported customer.', CPLINK_NAME)
                ),*/
                array(
                    'title' => __('Sync Field On Update', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'customers_sync_field_on_update',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'multiple' => true,
                    'options' => array(
                        'first_name' => 'First Name',
                        'last_name' => 'Last Name',
                        'email' => 'Email',
                        'password' => 'Password',
                    ),
                    'value' => '',
                    'default' => 'first_name',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Create Address On Sync', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'create_address_on_sync',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('Set yes to create new customer addresses in synchronization process if customer addresses does not exsist in WooCommerce.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Update Address On Sync', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'update_address_on_sync',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('Set yes to update customer addresses in synchronization process if customer addresses does not exsist in WooCommerce.', CPLINK_NAME)
                ),
                /*array(
                    'title' => __('Assign Customer to Tax Group', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'assign_customer_to_tax_group',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'options' => array(
                        '0' => 'No',
                        '1' => 'Yes',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('Set yes to assign customer to WooCommerce "Customer Group" using the Sage customer "Tax Schedule" in synchronization process', CPLINK_NAME)
                ),*/
                array(
                    'title' => __('Customer Group for Exemption Customer', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'customer_group_for_exemption_customer',
                    'dependencies' => array(
                        'customers_enabled' => '1',
                        'assign_customer_to_tax_group' => '1'
                    ),
                    'options' => array(
                        '' => '',
                        'not_logged_in' => 'NOT LOGGED IN',
                        'general' => 'General',
                        'wholesale' => 'Wholesale',
                        'retailer' => 'Retailer',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('Select WooCommerce "Customer Group" for assigning to WooCommerce customers with Sage customer tax exemption in synchronization process', CPLINK_NAME)
                ),
                array(
                    'title' => __('Address Default Country', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'address_default_country',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'value' => '',
                    'default' => 'USA',
                    'description' => __('Use this value on customer address create/update when country not specified.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Send Email on Customer Create', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'send_email_on_customer_create',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ), 
                array(
                    'title' => __('Default Welcome Email', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'default_welcome_email',
                    'dependencies' => array(
                        'customers_enabled' => '1',
                        'send_email_on_customer_create' => '1'
                    ),
                    'options' => array(
                        'default' => 'Default',
                    ),
                    'value' => '',
                    'default' => 'Default',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Default Telephone', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'default_telephone',
                    'dependencies' => array(
                        'customers_enabled' => '1'
                    ),
                    'value' => '',
                    'default' => '',
                    'description' => __('Use this value on customer address import when telephone not specified.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Account Option', CPLINK_NAME),
                    'subtitle' => '',
                    'type' => 'html_view',
                    'id' => 'account_option',
//                    'dependencies' => array(
//                        'order_export_enabled' => '1'
//                    ),
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Information Management', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'enable_information_management',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Address Management', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'enable_address_management',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Billing Address Management', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'enable_billing_address_management',
                    'dependencies' => array(
                        'enable_address_management' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Shipping Address Management', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'enable_shipping_address_management',
                    'dependencies' => array(
                        'enable_address_management' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Registration', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'enable_registration',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Sage Account Info', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'sage_account_info',
                    'dependencies' => array(
                        'enable_registration' => '1'
                    ),
                    'options' => array(
                        'not' => 'Not Needed',
                        'required' => 'Required',
                        'optional' => 'Optional',
                    ),
                    'value' => '',
                    'default' => 'not',
                    'description' => __('This setting specifies whether sage account information is required on registration.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Add New Addresses', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'add_new_addresses',
                    'dependencies' => array(
                        'enable_registration' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('This setting specifies whether new addresses should be added for customer, based on sage customer addresses, if sage account information is specified on registration.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Forgot Password', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'enable_forgot_password',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Require Sage Account No', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'require_sage_account_no',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('This setting require sage account customer number and division number to be applied on customer login.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Require Warehouse Code', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'require_warehouse_code',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('This setting require warehouse code to be applied on customer login.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Require Login', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'require_login',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Require Login Whitelist', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'require_login_whitelist',
                    'dependencies' => array(
                        'require_login' => '1'
                    ),
                    'value' => '',
                    'default' => 'cms_index_index,cms_page_view,contact_index_index',
                    'description' => __("Full action names list of pages that don't need customer authentication. Comma separated. E.g. 'contact_index_index,catalogsearch_result_index,catalogsearch_advanced_index'", CPLINK_NAME)
                ),
            ),
            'products' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'products_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Use Default Warehouse Inventory', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'use_default_warehouse_inventory',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'depends_from' => 'products_enabled',
                    'dependance_value' => '1',
                    'description' => __("This setting controls whether the WooCommerce inventory is set from the default warehouse or the combination of all warehouses. This setting does not change what inventory is displayed in the catalog, but simply what quantity is populated into WooCommerce's inventory tables.", CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Sage Inventory', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'enable_sage_inventory',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __("", CPLINK_NAME)
                ),
//                array(
//                    'title' => __('Create Category by Product Line', CPLINK_NAME),
//                    'subtitle' => __('[store view]', CPLINK_NAME), 
//                    'type' => 'selectbox',
//                    'id' => 'create_category_by_product_line',
//                    'dependencies' => array(
//                        'products_enabled' => '1'
//                    ),
//                    'options' => array(
//                        '1' => 'Yes',
//                        '0' => 'No',
//                    ),
//                    'value' => '',
//                    'default' => '1',
//                    'depends_from' => 'products_enabled',
//                    'dependance_value' => '1',
//                    'description' => __("", CPLINK_NAME)
//                ),
                array(
                    'title' => __('Sync Field On Creation', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'sync_field_on_creation',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        'post_title' => 'Name',
                        'post_content' => 'Description',
                        'post_excerpt' => 'Short Description',
                        '_weight' => 'Weight',
                        'post_name' => 'URL Key',
                        '_price' => 'Price',
                        'tax_class' => 'Tax Class',
                        'picture' => 'Picture',
                        '_stock' => 'Stock QTY',
                    ),
                    'value' => '',
                    'default' => 'Name',
                    'multiple' => true,
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Sync Field On Update', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'products_sync_field_on_update',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        'post_title' => 'Name',
                        'post_content' => 'Description',
                        'post_excerpt' => 'Short Description',
                        '_weight' => 'Weight',
                        'post_name' => 'URL Key',
                        '_price' => 'Price',
                        'tax_class' => 'Tax Class',
                        'picture' => 'Picture',
                        '_stock' => 'Stock QTY',
                    ),
                    'value' => '',
                    'default' => 'Name',
                    'multiple' => true,
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Default Status', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'products_default_status',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Enabled',
                        '0' => 'Disabled',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __("This setting is used to set status for imported product.", CPLINK_NAME)
                ),
                array(
                    'title' => __('Default Visibility', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'products_default_visibility',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        'not_visible_individually' => 'Not Visible Individually',
                        'catalog' => 'Catalog',
                        'search' => 'Search',
                        'catalog_search' => 'Catalog, Search',
                    ),
                    'value' => '',
                    'default' => 'Not Visible Individually',
                    'description' => __('This setting is used to set visibility for imported product.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Stock Config Settings', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'stock_config_settings',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        'yes' => 'Yes',
                        'no' => 'No',
                        'use_global_settings' => 'Use Global Settings',
                    ),
                    'value' => '',
                    'default' => 'Use Global Settings',
                    'description' => __(' If YES: Manage Stock=&gt;YES, Use Config Settings=&gt; UN TICK<br>
                                    If No: Manage Stock=&gt;No, Use Config Settings=&gt; UN TICK<br>
                                    If Use Global Settings: Get Stock Options from default settings', CPLINK_NAME)
                ),
                /*array(
                    'title' => __('Default Attribute Set', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'default_attribute_set',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        'default' => 'Default',
                    ),
                    'value' => '',
                    'default' => 'Default',
                    'description' => __('This setting is used to set default attribute for imported product.', CPLINK_NAME)
                ),*/
                array(
                    'title' => __('Default Weight', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'products_default_weight',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'value' => '',
                    'default' => '',
                    'description' => __('This setting is used to set default weight on product while adding/updating if Sage item weight is not set or is 0.', CPLINK_NAME)
                ),
                /*array(
                    'title' => __('Sync Custom Attributes (UDFs) on Creation', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'sync_custom_attributes_uDFs_on_creation',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),*/
                /*array(
                    'title' => __('Sync Custom Attributes (UDFs) on Update', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'sync_custom_attributes_uDFs_on_update',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),*/
                array(
                    'title' => __('On Sage Internet Enabled disable', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'on_sage_internet_enabled_disable',
                    'dependencies' => array(
                        'products_enabled' => '1'
                    ),
                    'options' => array(
                        'none' => 'None',
                        'disable_product' => 'Disable Product',
                    ),
                    'value' => '',
                    'default' => 'Default',
                    'description' => __('This setting specifies what to do when already imported product Internet Enabled unchecked from Sage.', CPLINK_NAME)
                ),
            ),
            'pricing' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'pricing_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Enable Sage Pricing', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'enable_sage_pricing',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
                /*array(
                    'title' => __('Clear cache', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'clear_pricing_cache',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'dependencies' => array(
                        'enable_sage_pricing' => '1'
                    ),
                    'description' => __('Set Yes to clear the full page cache after price codes import.', CPLINK_NAME)
                ),*/
                array(
                    'title' => __('Default Price Level', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'default_price_level',
                    'options' => array(
                        'from_customer' => 'From Customer',
                        'by_customer_price_code' => 'By Customer Price Code',
                    ),
                    'value' => '',
                    'default' => 'From Customer',
                    'dependencies' => array(
                        'pricing_enabled' => '1'
                    ),
                    'description' => __('', CPLINK_NAME)
                ),
            ),
            'sales_order' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'sales_order_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Sales Order Cut of Months', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'salesorders_cut_of_months',
                    'dependencies' => array(
                        'sales_order_enabled' => '1'
                    ),
                    'value' => '',
                    'default' => '',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Redirect to Pending Orders', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'sales_order_redirect_to_pending_orders',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('Rewrite links on checkout success page by elink pending order links.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Allow Reorder', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'sales_order_allow_reorder',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
            ),
            'invoices' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'invoices_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Invoices Cut of Months', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'text',
                    'id' => 'invoices_cut_of_months',
                    'dependencies' => array(
                        'invoices_enabled' => '1'
                    ),
                    'value' => '',
                    'default' => '',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Allow Reorder', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'invoices_allow_reorder',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
            ),
            'shipping_methods' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'shipping_methods_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Shipping Methods List', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'html_view',
                    'id' => 'shipping_methods_list',
                    'dependencies' => array(
                        'shipping_methods_enabled' => '1'
                    ),
                    'multiple' => true,
                    'options' => array(
                        'cash_on_delivery' => 'Cash On Delivery',
                        'u_s_postal_service' => 'U.S. POSTAL SERVICE',
                        'shipping_product_line' => 'Shipping Product Line',
                        'rate_times_weight' => 'Rate Times Weight',
                        'flat_rate_base_on_amount' => 'Flat Rate Base on Amount',
                        'ups_2nd_day_air' => 'UPS 2ND DAY AIR',
                        'ups_next_day_air' => 'UPS NEXT DAY AIR',
                    ),
                    'value' => '',
                    'default' => 'first_name',
                    'description' => __('Shipping methods list filled by shippingmethods API ', CPLINK_NAME).$shipping_methods_html
                ),
            ),
            'payment_types' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'payment_types_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
            ),
            'terms_code' => array(
                array(
                    'title' => __('Sync Enabled', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'terms_code_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('', CPLINK_NAME)
                ),
                array(
                    'title' => __('Payment Validation By Terms Code', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'cc_validation_by_terms_code',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('This settings specifies whether available payment methods based on Sage customers terms code.', CPLINK_NAME)
                ),
                array(
                    'title' => __('Active Payment Methods', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'active_payment_methods_terms_list',
                    'dependencies' => array(
                        'cc_validation_by_terms_code' => '1'
                    ),
                    'multiple' => true,
                    'options' => $woo_active_payments,
                    'value' => '',
                    'default' => '00',
                    'description' => __('This settings specifies to set Active Payment Methods', CPLINK_NAME)
                ),
                array(
                    'title' => __('Active Payments Terms Code List', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'cc_terms_list',
                    'dependencies' => array(
                        'cc_validation_by_terms_code' => '1'
                    ),
                    'multiple' => true,
                    'options' => $terms_code,
                    'value' => '',
                    'default' => '00',
                    'description' => __('This settings specifies whether to filter available payment methods based on Sage customers credit hold value.', CPLINK_NAME)
                ), 
                array(
                    'title' => __('Active Payments Validation By Credit Hold', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME), 
                    'type' => 'selectbox',
                    'id' => 'cc_validation_by_credit_hold',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('This settings specifies whether to filter available payment methods based on Sage customers credit hold value.', CPLINK_NAME)
                ), 
            ),
        ),
    )
);
$options['shipping_methods'] = array(
    'shipping_methods' => array(

    )
);
$options['shipping_methods']['shipping_methods'] = $shipping_methods_settings;
/*$shipping_methods_settings
$options*/

//Building Option Area
$current_tab = (isset($_GET['tab']) && isset($tabs[ $_GET['tab'] ]))?$_GET['tab']:'general';
//CPLINK_Admin::set_current_tab( $current_tab );

echo '<div id="cplink_option_area"><form id="cplink_settings_form" method="post" action="options.php">'; // option area div -
settings_fields('cplink_option_settings');
do_settings_sections('cplink_option_settings');
echo '<input type="hidden" name="tab" value="'.$current_tab.'">';

echo '<h1>' . $tabs[$current_tab]['title'].' - '.__('CertiPro E-Link Options', CPLINK_NAME) . '</h1>';
echo '<div class="clink_settings_wrapper">'; //clink settings wrapper
echo '<div class="clink_settings_notices">';
settings_errors();
echo '</div>';
/*
if ( false !== $_REQUEST['settings-updated'] ) {
    echo '<div> <p><strong>'. _e( 'Options saved', CPLINK_NAME ).'</strong></p></div>';
}*/
echo '<div class="tabbed_settings">'; //tabs wrapper
echo '<div class="tabs_wrapper">';
foreach ($tabs as $tab) {
    $tab_class =" ";
    if( $current_tab == $tab['id'] )
        $tab_class.=" active";
    echo '<div class="tab"><a href="'.$menu_page_url.'&tab='.$tab['id'].'" id="' . $tab['id'] . '" class="'.$tab_class.'">' . $tab['title'] . '</a></div>';
}
echo '</div>';
echo '<div class="tabs_content_wrapper">';
if ( $current_tab ) {
    echo '<div class="tab_content" id="' . $current_tab . '_tab">';
    echo '<table id="cplink_option_content"> <tr> '; // #cplink_option_content -
    //	Building Option Menu Sections
    echo '<td class="cplink_option_sections col-md-3"> <ul class="">';
    $tab_sections = $sections[$current_tab];
    foreach ($tab_sections as $section) {
        echo '<li class="i_cplink_section_tab"><a href="#" id="i_' . $section['id'] . '" >' . $section['title'] . '</a></li>';
    }
    echo '</ul></td>';


    //	Building Options Content Sections
    echo '<td class="cplink_option_fields_div col-md-12">';
    $option_div_class = '';
    if( $current_tab == 'shipping_methods' )
        $option_div_class = 'col-md-4';

    $tab_section_options = $options[$current_tab];
    $dependencies_array = [];
    foreach ($tab_section_options as $option => $fileds) {
        echo '<div id="i_' . $option . '_option" class="i_cplink_section_content">';

        foreach ($fileds as $field) {
            if(CPLINK::isset_return($field, 'dependencies')){
                $dependencies_array['field_'.$field['id']] = $field['dependencies'];
            }
            //'.( isset($field["depends_from"]) ? 'data-depends_from="field_'.$field["depends_from"].'_"' : '' ).' '.( isset($field["dependance_value"]) ? 'data-dependance_value="'.$field["dependance_value"].'"' : '' ).'
            echo '<div class="cplink_option_field_div field_' . $field['id'] . '_div '.$option_div_class.'" >';

            if (isset($field['global_option']) && $field['global_option']) {
                $f_value = get_option($field['id']);
            } else {
                $f_value = (isset($cplink_options[$field['id']])) ? $cplink_options[$field['id']] : '';
                if (isset($field['id_key'])) {
                    $f_value = ($f_value) ? $f_value[$field['id_key']] : '';
                } else {
                    $field['id_key'] = false;
                }
            }

            switch ($field['type']) {
                case "text";
                    echo CPLINK_Admin::create_section_for_text($field, $f_value);
                    break;

                case "textarea":
                    echo CPLINK_Admin::create_section_for_textarea($field, $f_value);
                    break;

                case "textarea_editor":
                    CPLINK_Admin::create_section_for_textarea_editor($field, $f_value);
                    break;

                case "checkbox":
                    echo CPLINK_Admin::create_section_for_checkbox($field, $f_value);
                    break;

                case "radio":
                    echo CPLINK_Admin::create_section_for_radio($field, $f_value);
                    break;

                case "selectbox":
                    echo CPLINK_Admin::create_section_for_selectbox($field, $f_value);
                    break;

                case "email";
                    echo CPLINK_Admin::create_section_for_email($field, $f_value);
                    break;

                case "number":
                    echo CPLINK_Admin::create_section_for_number($field, $f_value);
                    break;

                case "post_selector":
                    echo CPLINK_Admin::create_section_for_post_selector($field, $f_value);
                    break;

                case "post_selectbox":
                    echo CPLINK_Admin::create_section_for_post_selectbox($field, $f_value);
                    break;

                case "image_url":
                    echo CPLINK_Admin::create_section_for_image_url($field, $f_value);
                    break;

                case "date_picker":
                    echo CPLINK_Admin::create_section_for_date_picker($field, $f_value);
                    break;

                case "color_picker":
                    echo CPLINK_Admin::create_section_for_color_picker($field, $f_value);
                    break;

                case "intro_view":
                    echo CPLINK_Admin::create_section_for_intro_view($field, $f_value);
                    break;

                case "html_view":
                    echo CPLINK_Admin::create_section_for_html_view($field, $f_value);
                    break;
            }

            echo '</div>';
        }

        echo '</div>';
    }
    echo '</td> ';

    ?>
    <script>
        var dependencies_array = <?php echo json_encode($dependencies_array); ?>;
    </script>
    <?php

    echo '</tr></table>'; // - #cplink_option_content
    echo '</div>';//tab end
}
echo '</div>';//tabs content wrapper end
echo '</div>';//tabs wrapper end
echo get_submit_button();
echo '</div>';//clink settings wrapper
echo '</form></div>'; // - option area div