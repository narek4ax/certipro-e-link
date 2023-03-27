<?php

global $CP_Sage;

class CPLINK
{
    private static $initiated = false;
    public static $table_prefix = '';
    public static $user_data = array();
    public static $scopConfig = array();
    public static $module_options = array();
    public static $shipping_options = array();
    public static $page_endpoints = array();
    public static $import_statuses = array(
        '0' => 'In Progress',
        '1' => 'Completed',
        '2' => 'Failed',
        '3' => 'Waiting for next functionality'
    );
    public static $import_run_results = array(
        '0' => 'Request In Progress',
        '1' => 'Request completed successfully',
        '2' => 'Request Failed',
        '3' => 'Request completed successfully, But we need to continue the functionality!!!'
    );
    public static $import_schedules = array(
        /*'warehouses' => array(
            'name'=>'warehouses',
            'interval'=>24*60,
			'type' => 'sync'
        ),
        'customers' => array(
            'name'=>'customers',
            'interval'=>72*60,
            'before_run' => array('customers_addresses', 'customers_taxexemptions'),
			'type' => 'sync'
        ),
        'customers_addresses' => array(
            'name'=>'customers_addresses',
            'interval'=>24*60,
			'type' => 'sync'
        ),
        'customers_taxexemptions' => array(
            'name'=>'customers_taxexemptions',
            'interval'=>24*60,
			'type' => 'sync'
        ),*/
        'users' => array(
            'name'=>'users',
            'interval'=>3*60,
            'before_run' => array('customers', 'customers_addresses', 'customers_taxexemptions'),
            'type' => 'purge'
        ),
        /*'productlines' => array(
            'name'=>'productlines',
            'interval'=>60*60,
			'type' => 'global'
        ),
        'inventory' => array(
            'name'=>'inventory',
            'interval'=>5,
            'before_run' => array(),
            'after_run' => array(),
			'type' => 'sync'
        ),*/
        'products' => array(
            'name'=>'products',
            'interval'=>2,
            'before_run' => array(
                'inventory' => array('name'=> 'inventory', 'type'=>'purge'),
                'pricing' => array('name'=>'pricecodes', 'type'=>'purge')
            ),
            'after_run' => array(),
            'type' => 'purge'
        ),
        /*'pricing' => array(
            'name'=>'pricecodes',
            'interval'=>5,
			'type' => 'sync'
        ),*/
        'pricelevels_customerpricecodes' => array(
            'name'=>'pricelevels_customerpricecodes',
            'interval'=>2,
            'type' => 'purge'
        ),
        'sales_orders' => array(
            'name'=>'salesorders',
            'interval'=> 2,
            'before_run' => array(
                'salesorders_history'=>array('name'=> 'salesorders_history', 'type'=>'sync')
            ),
            'type' => 'purge'
        ),
        'invoices' => array(
            'name'=>'invoices',
            'interval'=>2,
            'before_run' => array(
                'invoices_trackings'=>array('name'=> 'invoices_trackings', 'type'=>'purge'),
                'invoices_history'=>array('name'=> 'invoices_history', 'type'=>'sync')
            ),
            'after_run' => array(),
            'type' => 'purge'
        ),
        /*'invoices_trackings' => array(
            'name'=>'invoices_trackings',
            'interval'=>24*60,
			'type' => 'sync'
        ),
        'shipping_methods' => array(
            'name'=>'shippingmethods',
            'interval'=>24*60,
			'type' => 'sync'
        ),
        'payment_types' => array(
            'name'=>'paymenttypes',
            'interval'=>24*60,
			'type' => 'sync'
        ),
        'terms_code' => array(
            'name'=>'termscode',
            'interval'=>24*60,
			'type' => 'sync'
        ),*/
    );
    protected static $invoice_endpoints = array(
        'invoice', 'invoices'
    );

    public static $custom_crons = array(
        'custom1.php' => array(
            'name'=>'custom1.php',
            'interval'=>24*60
        ),
    );


    public static function init()
    {
        self::cp_forcelogin();
        if (!self::$initiated) {
            self::init_hooks();
        }

    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        global $wpdb;
        self::$initiated = true;
        self::$table_prefix = CPLINK_DB_PREFIX;
        global $CP_Sage, $cp_scope_cf, $cp_modules_cf, $cp_shipping_methods;
        if (!$CP_Sage) {
            $scopConfig = $cp_scope_cf = self::$scopConfig = get_option(CPLINK::get_settings_name(), true);
            $cp_modules_cf = self::$module_options = get_option(CPLINK::get_settings_name('modules'), true);
            $cp_shipping_methods = self::$shipping_options = get_option(CPLINK::get_settings_name('shipping_methods'), true);
            $CP_Sage = new CP_Sage($scopConfig);
        }

        $invoice_endpoints = self::$invoice_endpoints;

        /*
        $cp_view_invoice = self::get_user_meta('cp_view_invoice');
        if( $cp_view_invoice ) {
        } else {
            $invoice_endpoints = array();
        }*/

        $page_endpoints = self::$page_endpoints = $invoice_endpoints;
        add_filter( 'query_vars', array('CPLINK', 'cp_query_vars'), 0 );
        remove_action('woocommerce_order_details_after_order_table', 'woocommerce_order_again_button');
        add_action('woocommerce_order_details_before_order_table', 'woocommerce_order_again_button');

        add_action('wp_enqueue_scripts', array('CPLINK', 'load_resources'));

        add_filter('woocommerce_product_tabs', array('CPLINK', 'woo_new_product_tab'), 10, 1);
        add_filter('woocommerce_account_menu_items', array('CPLINK', 'customize_my_account_links'), 10, 1);

        add_action('woocommerce_thankyou', array('CPLINK', 'save_order_to_queue'), 10, 1);

        add_action('woocommerce_ordered_again', array('CPLINK', 'ordered_again'));
        add_filter('woocommerce_valid_order_statuses_for_order_again', array('CPLINK', 'order_again_statuses'), 10, 1);
        //add_filter('woocommerce_order_button_text', array('CPLINK', 'order_again_button_text'), 10);
        add_action('woocommerce_thankyou', array('CPLINK', 'create_order_note'));
        add_action('woocommerce_thankyou', array('CPLINK', 'custom_thank_you_page'));
        add_action('woocommerce_cart_is_empty', array('CPLINK', 'reset_session_flag'));

        add_filter('the_title', array('CPLINK', 'wc_page_endpoint_title'), 1, 2);
        add_filter('woocommerce_locate_template', array('CPLINK', 'woo_plugin_template'), 100, 3);
        //if(  ){ can be a logic for each user individually // sales_order_enabled
        add_filter( 'woocommerce_my_account_my_orders_query', array('CPLINK', 'woo_orders_query'), 10, 1 );
        add_action('woocommerce_before_account_orders_pagination', array('CPLINK', 'show_sage_orders'), 10);
        add_action('woocommerce_account_view-order_endpoint', array('CPLINK', 'woocommerce_account_view_order'), 1, 1);
        //}

        /*add_action( 'woocommerce_register_form', array('CPLINK', 'woocommerce_registration_custom_fields') );*/
        add_action( 'woocommerce_register_form_start', array('CPLINK', 'woocommerce_registration_custom_fields_beggining') );
        add_action( 'woocommerce_register_form', array('CPLINK', 'woocommerce_registration_custom_fields_end') );
        add_filter( 'woocommerce_registration_errors', array('CPLINK', 'woocommerce_registration_custom_fields_errors'), 10, 3 );
        add_action( 'woocommerce_created_customer', array('CPLINK', 'woocommerce_registration_custom_fields_save_from_front') );

        //Disable Payment Gateway By using Sage logic
        add_filter( 'woocommerce_available_payment_gateways', array('CPLINK', 'woo_available_payment_gateways'), 99, 1 );

        add_filter( 'option_woocommerce_manage_stock', array('CPLINK', 'filter_woo_manage_stock'), 10, 1 );


        $cp_inactive_user = CPLINK::get_user_meta('cp_inactive_user');
        add_action('get_header', array('CPLINK', 'sage_check_view_permissions'), 10 );

        if( $cp_inactive_user ){
            add_action('get_header', array('CPLINK', 'sage_logout_inactive_user'), 10 );
            add_filter('login_message', array('CPLINK', 'sage_inactive_login_message'), 10 );
        }

        //Woo product price by using sage customer price level
        /*add_filter( 'woocommerce_product_get_price', array('CPLINK', 'woo_product_price'), 90, 2 );
        add_filter( 'woocommerce_product_get_regular_price', array('CPLINK', 'woo_product_price'), 90, 2 );
        add_filter( 'woocommerce_product_variation_get_price', array('CPLINK', 'woo_product_price'), 90, 2 );
        add_filter('woocommerce_product_variation_get_regular_price', array('CPLINK', 'woo_product_price'), 90, 2);
        add_filter( 'woocommerce_product_get_sale_price', array('CPLINK', 'woo_product_price'), 90, 2 );*/
        //add_filter( 'woocommerce_product_get_price', array('CPLINK', 'woo_product_price'), 90, 2 );

        $enable_sage_pricing = CPLINK::get_module_option('enable_sage_pricing');
        if( $enable_sage_pricing ) {
            add_filter('woocommerce_cart_item_price', array('CPLINK', 'woo_cart_item_price'), 10, 3);
            add_filter('woocommerce_get_price_html', array('CPLINK', 'woo_price_html'), 99, 2);
        }

        add_filter( 'woocommerce_cart_product_subtotal', array('CPLINK', 'woo_cart_product_subtotal'), 99, 3 );
        add_filter( 'woocommerce_before_calculate_totals', array('CPLINK', 'woo_before_calculate_totals'), 99, 1 );
        add_filter( 'woocommerce_shipping_fields', array( 'CPLINK', 'add_shipping_address_custom_field' ), 10, 1 );
        //add_filter( 'woocommerce_localisation_address_formats', array('CPLINK','cp_custom_address_format') );

        add_filter( 'pre_option_woocommerce_enable_myaccount_registration', array( 'CPLINK', 'cp_users_can_register' ), 10, 1 );
        add_filter( 'pre_option_woocommerce_registration_generate_password', array( 'CPLINK', 'cp_users_allow_generate_password' ), 10, 1 );
        add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', array( 'CPLINK', 'cp_users_allow_signup_and_login_from_checkout' ), 10, 1 );
        add_filter( 'pre_option_woocommerce_enable_checkout_login_reminder', array( 'CPLINK', 'cp_users_enable_checkout_login_reminder' ), 10, 1 );
        add_filter( 'show_password_fields', array( 'CPLINK', 'disable_lost_pass' ), 10 );
        add_filter( 'allow_password_reset', array( 'CPLINK', 'disable_lost_pass' ), 10 );
        add_filter( 'gettext',              array( 'CPLINK', 'remove_lost_pass' ), 10 );

        add_action('wp_authenticate', array( 'CPLINK','cp_can_user_login'), 10, 1);

        foreach ($page_endpoints as $page_endpoint) {
            add_rewrite_endpoint($page_endpoint, EP_PAGES);
            add_action('woocommerce_account_' . $page_endpoint . '_endpoint', array('CPLINK', 'account_' . $page_endpoint . '_endpoint_content'), 10, 1);
        }

        self::schedule_cron_jobs();
        //self::cplink_run_import(); exit;
        //if( defined('DOING_CRON') && DOING_CRON )
        //add_action('cplink_schedule_import', array('CPLINK', 'cplink_run_import'));

        $import_schedules = self::$import_schedules;

        foreach ($import_schedules as $import_schedule){

            $schedule_name = 'cplink_schedule_import_'.$import_schedule['name'];
            //$schedule_fn_name = 'cplink_run_import_'.$import_schedule['name'];

            add_action($schedule_name, array('CPLINK', 'cplink_run_import'), 10, 1);
        }

        add_action('cplink_schedule_export', array('CPLINK', 'cplink_run_export'));

        $settings_name = self::get_settings_name();

        do_action('cplink_init_ready');
    }

    public static function max_server_ini( $max_execution_time = 0, $memory_limit = '2048M' )
    {
        ini_set('max_execution_time', $max_execution_time);
        ini_set('memory_limit', $memory_limit);
    }

    public static function get_scopConfig($option_name)
    {
        if (!count(self::$scopConfig)) {
            self::$scopConfig = get_option(CPLINK::get_settings_name(), true);
        }
        $scopConfig = self::$scopConfig;
        if (isset($scopConfig[$option_name]))
            return $scopConfig[$option_name];

        return false;
    }

    public static function get_module_option($module_name)
    {
        if (!count(self::$module_options)) {
            self::$module_options = get_option(CPLINK::get_settings_name('modules'), true);
        }
        $module_options = self::$module_options;
        if (isset($module_options[$module_name]))
            return $module_options[$module_name];

        return false;
    }

    public static function is_woo_active()
    {
        $pluginList = get_option('active_plugins');
        $plugin = 'woocommerce/woocommerce.php';
        if (in_array($plugin, $pluginList)) {
            // Plugin 'mg-post-contributors' is Active
        }
        if (in_array($plugin, $pluginList))
            return true;
        return false;
    }

    public static function plugin_activation()
    {
        global $wpdb;
        global $table_prefix;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_prefix = CPLINK_DB_PREFIX;

        require_once(CPLINK_PLUGIN_DIR . 'lib/data/dbDelta.php');

        update_option('cplink_db_version', CPLINK_DB_VERSION);
    }


    /**
     * Removes all connection options
     * @static
     */
    public static function plugin_deactivation()
    {
        wp_clear_scheduled_hook('cplink_schedule_import');
    }

    public static function cplink_cron_schedules($schedules)
    {
        $import_schedules = self::$import_schedules;

        foreach ($import_schedules as $import_schedule){
            $schedule_interval = $import_schedule['interval'];
            $schedules['cplink_'.$schedule_interval.'_minute'] = array(
                'interval' => 60 * $schedule_interval,
                'display' => 'Every '.$schedule_interval.' minute'
            );
        }

        /*$schedules['cplink_10_minute'] = array(
            'interval' => 60 * 10,
            'display' => 'Every 10 minute'
        );*/

        return $schedules;
    }

    public static function schedule_cron_jobs()
    {
        $import_schedules = self::$import_schedules;
        // 'hourly', 'daily', and 'twicedaily'.
        //wp_clear_scheduled_hook( 'cplink_schedule_import' );

        /*if (!wp_next_scheduled('cplink_schedule_import'))
            wp_schedule_event(time(), 'cplink_10_minute', 'cplink_schedule_import');*/

        if (!wp_next_scheduled('cplink_schedule_export'))
            wp_schedule_event(time(), 'daily', 'cplink_schedule_export');

        foreach ($import_schedules as $import_module => $import_schedule){
            $schedule_name = 'cplink_schedule_import_'.$import_schedule['name'];

            $schedule_interval = $import_schedule['interval'];
            if (!wp_next_scheduled($schedule_name, array($import_module)))
                wp_schedule_event(time(), 'cplink_'.$schedule_interval.'_minute', $schedule_name, array($import_module) );
        }
    }

    public static function load_resources()
    {
        wp_enqueue_style('cplink_datatable_style', CPLINK_PLUGIN_URL . 'resources/js/datatable/jquery.dataTables.min.css', array(), CPLINKVersion, 'all');
        wp_enqueue_style('cplink_dataTables_dateTime_style', CPLINK_PLUGIN_URL . 'resources/js/datatable/dataTables.dateTime.min.css', array(), CPLINKVersion, 'all');
        wp_enqueue_script('cplink_datatable_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/jquery.dataTables.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_datatable_moment_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/moment.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_dataTables_dateTime_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/dataTables.dateTime.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_datatable_colReorder_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/dataTables.colReorder.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_basictable_min_js', CPLINK_PLUGIN_URL . 'resources/js/basictable/basictable.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_printThis_js', CPLINK_PLUGIN_URL . 'resources/js/print/printThis.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_jquery_basictable_min_js', CPLINK_PLUGIN_URL . 'resources/js/basictable/jquery.basictable.min.js', array('jquery'), CPLINKVersion, true);
        //CPLINK_PLUGIN_URL.'resources/style/admin_style.css
        wp_enqueue_style('cplink_style', CPLINK_PLUGIN_URL . 'resources/style/front_style.css', array(), CPLINKVersion, 'all');
        wp_enqueue_style('cplink_responsive_style', CPLINK_PLUGIN_URL . 'resources/style/front-responsive.css', array(), CPLINKVersion, 'all');
        wp_enqueue_style('cplink_basictable_min_css', CPLINK_PLUGIN_URL . 'resources/js/basictable/basictable.min.css', array(), CPLINKVersion, 'all');
        //wp_enqueue_script('slick_script', CPLINK_PLUGIN_URL . 'resources/js/slick/slick.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_front_script', CPLINK_PLUGIN_URL . 'resources/js/front_js.js', array('jquery'), CPLINKVersion, true);
    }

    public static function register_cpt_()
    {
    }

    public static function get_country_iso2($code_iso3 = '')
    {
        global $countries_iso3;
        require_once(CPLINK_PLUGIN_DIR . 'lib/data/counties-iso3.php');

        $iso_key = array_search($code_iso3, $countries_iso3);
        if ($iso_key !== false) {
            return $iso_key;
        }
        return $code_iso3;
    }

    public static function get_country_iso3($code_iso2 = '')
    {
        global $countries_iso3;
        require_once(CPLINK_PLUGIN_DIR . 'lib/data/counties-iso3.php');

        if (isset($countries_iso3[$code_iso2])) {
            return $countries_iso3[$code_iso2];
        }
        return $code_iso2;
    }

    public static function isset_return($array = array(), $key, $default = '')
    {
        if (!empty($array) && is_array($array) && isset($array[$key])) {
            return $array[$key];
        }
        return $default;
    }

    public static function i_real_set($a){
        if( isset($a) && !empty($a) )
            return true;
        return false;
    }

    public static function get_settings_name($settings_tab = '')
    {
        $settings_name = CPLINK_SETTINGS_NAME;

        if ($settings_tab == 'general')
            $settings_tab = '';

        if ($settings_tab)
            $settings_name .= '-' . $settings_tab;

        return $settings_name;
    }

    public static function cp_query_vars($vars)
    {
        $vars[] = 'invoices';
        return $vars;
    }
    /*
     * Woo Functionality
     */
    public static function wc_page_endpoint_title($title)
    {
        global $wp_query, $wp;

        if (!is_null($wp_query) && !is_admin() && is_main_query() && is_page() && in_the_loop()) {
            $title = self::wc_endpoint_title($title);
        }

        return $title;
    }

    public static function wc_endpoint_title($title)
    {
        global $wp_query, $wp;

        $endpoint_title = '';
        if (is_wc_endpoint_url()) { //self::get_module_option('sales_order_enabled') &&
            $endpoint = WC()->query->get_current_endpoint(); //echo '--'.$endpoint.'--';
            if ($endpoint == 'view-order') {
                $title = 'Order';
                $order_n = '';
                $order_id = $wp->query_vars['view-order'];
                $order = wc_get_order($order_id);
                if ($order) {
                    $order_n = $order->get_order_number();
                    $cp_sales_order_number = $order->get_meta('cp_sales_order_number'); //$cp_sales_order_number = get_post_meta($order_id, 'cp_sales_order_number', true);

                    if ($cp_sales_order_number) {
                        $title = 'Sales Order';
                        $order_n = $cp_sales_order_number;
                    }
                }
                /* translators: %s: order number */
                $endpoint_title = sprintf(__($title . ' #%s', CPLINK_NAME), $order_n);
                $title = $endpoint_title ? $endpoint_title : $title;

                remove_filter('the_title', 'wc_page_endpoint_title');
            }
        }

        if (isset($wp_query->query_vars['invoices'])) {
            $title = 'Invoices';
            $invoice_n = '';
            $invoice_n = get_query_var('invoices');

            if ($invoice_n) {
                $title = 'Invoice';
                $invoice_item = self::get_sage_invoice($invoice_n, 'invoice_no');
                if (count($invoice_item))
                    $invoice_n = $invoice_item['invoice_no'];
                $endpoint_title = sprintf(__($title . ' #%s', CPLINK_NAME), $invoice_n);
            }
            $title = $endpoint_title ? $endpoint_title : $title;

            remove_filter('the_title', 'wc_page_endpoint_title');
        }

        return $title;
    }

    public static function woo_orders_query($args)
    {
        $args['posts_per_page'] = -1;
        return $args;
    }

    /**
     * Filter Woo Inventory Settings
     */
    public static function filter_woo_manage_stock( $option_val )
    {
        $stock_config_settings = CPLINK::get_module_option('stock_config_settings');

        switch ($stock_config_settings){
            case 'yes':
                $option_val = 'yes';
                break;
            case 'no':
                $option_val = 'no';
                break;
            case 'use_global_settings':

                break;
        }

        return $option_val;
    }

    public static function sage_check_view_permissions(  )
    {
        global $wp;
        $invoice_endpoints = self::$invoice_endpoints;

        if( count($wp->query_vars) ){
            $cp_view_invoice = self::get_user_meta('cp_view_invoice');
            if( !$cp_view_invoice ) {
                foreach ($invoice_endpoints as $invoice_enpoint){
                    if( isset( $wp->query_vars[$invoice_enpoint] ) ){
                        wp_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) ); exit;
                    }
                }
            }
        }

    }

    /**
     * Filter Inactive User
     */

    public static function sage_logout_inactive_user(  )
    {
        if ( !current_user_can('administrator') ) {
            $redirect_url = home_url();
            wp_logout();
            wp_redirect( $redirect_url, 302 );
        }
    }

    public static function sage_inactive_login_message(  )
    {
        if ( !current_user_can('administrator') ) {
            $message = '<p class="message"><b>Your account is Inactive. Please contact with site Administrator.</b></p>';
            return $message;
        }
    }

    /**
     * Add a custom product data tab
     */
    public static function woo_new_product_tab($tabs)
    {

        // Adds the new tab

        $tabs['test_tab'] = array(
            'title' => __('New Product Tab', CPLINK_NAME),
            'priority' => 50,
            'callback' => array('CPLINK', 'woo_new_product_tab_content')
        );

        return $tabs;

    }

    public static function woo_new_product_tab_content()
    {
        // The new tab content
        echo '<h2>New Product Tab</h2>';
        echo '<p>Here\'s your new product tab.</p>';
    }

    public static function customize_my_account_links($menu_links)
    {
        /*global $cp_modules_cf;
        $enable_information_management = self::isset_return($cp_modules_cf, 'enable_information_management');
        if($enable_information_management == '0'){
            unset($menu_links['edit-account']);
        }*/
        //edit-account
        //unset($menu_links['downloads']); // GX should add option for this
        $pos = 2;

        $cp_view_invoice = self::get_user_meta('cp_view_invoice');
        if( $cp_view_invoice ) { //self::get_module_option('invoices_enabled')
            $new = array('invoices' => 'Invoices');
            $menu_links = array_slice($menu_links, 0, $pos, true)
                + $new
                + array_slice($menu_links, $pos, NULL, true);
        }
        return $menu_links;
    }

    public static function account_invoices_endpoint_content($paged)
    {
        global $wp_query;

        $paged = get_query_var('invoices');
        $page = ($paged && is_numeric($paged)) ? absint($paged) : 1;
        $items_per_page = 10;
        $user_id = get_current_user_id();
        if (self::isset_return($_GET, 'user_id')) {
            $user_id = $_GET['user_id'];
        }

        $invoices = self::get_sage_invoices($paged, $items_per_page);
        $total = ceil( count( self::get_sage_invoices(1, -1, 'id') ) / $items_per_page );
        echo '<div class="cp_invoices_div">';
        wc_get_template(
            'sage/invoices/index.php', array('invoices' => $invoices)
        ); //require_once(CPLINK_PLUGIN_DIR . 'templates/woocommerce/sage/invoices/index.php');

        wc_get_template(
            'sage/pagination.php', array('base' => esc_url( wc_get_endpoint_url( 'invoices' ) ), 'total' => $total, 'page' => $page, 'items_per_page' => $items_per_page)
        );
        echo '</div>';
    }

    public static function account_invoice_endpoint_content($invoice_id)
    {
        global $wp_query;
        //$invoice = get_query_var( 'invoice' );
        echo '<div class="cp_invoices_div cplink_print_area">';
        wc_get_template(
            'sage/invoices/single.php', array('invoice_id' => $invoice_id)
        ); //require_once(CPLINK_PLUGIN_DIR . 'templates/woocommerce/sage/invoices/single.php');
        echo '<div>';
    }

    public static function woo_plugin_template($template, $template_name, $template_path)
    {
        /*if( !self::get_module_option('sales_order_enabled') ) { //
            if( strpos($template_name, 'myaccount/') !== false || strpos($template_name, 'order/') !== false )
                return $template;
        }*/

        global $woocommerce;
        $_template = $template;
        if (!$template_path)
            $template_path = $woocommerce->template_url;

        $plugin_path = CPLINK_PLUGIN_DIR . '/templates/woocommerce/';

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );

        if (!$template && file_exists($plugin_path . $template_name))
            $template = $plugin_path . $template_name;
        if (!$template)
            $template = $_template;

        return $template;
    }

    public static function woocommerce_account_view_order($order_id)
    {

        $order = wc_get_order($order_id);

        if (!$order || !current_user_can('view_order', $order_id)) {
            if( $_GET && isset( $_GET['sales_order'] ) ) {
                $cp_sales_order_number = $order_id;
            } else {
                $cp_sales_order_number = get_post_meta($order_id, 'cp_sales_order_number', true);
            }

            if ($cp_sales_order_number) {
                remove_action('woocommerce_account_view-order_endpoint', 'woocommerce_account_view_order');
                wc_get_template(
                    'sage/order/view-order.php',
                    array(
                        //'status' => $status, // @deprecated 2.2.
                        //'order' => $order,
                        'cp_sales_order_number' => $cp_sales_order_number,
                    )
                );
            }
        }

    }


    public static function sage_account_view_order($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order || !current_user_can('view_order', $order_id)) {
            $cp_sales_order_number = get_post_meta($order_id, 'cp_sales_order_number', true);
            if ($cp_sales_order_number) {
                // Backwards compatibility.
                $status = new stdClass();
                $status->name = wc_get_order_status_name($order->get_status());
                remove_action('woocommerce_account_view-order_endpoint', 'woocommerce_account_view_order');

                wc_get_template(
                    'myaccount/view-order.php',
                    array(
                        'status' => $status, // @deprecated 2.2.
                        'order' => $order,
                        'order_id' => $order->get_id(),
                    )
                );
            }
        }

    }

    public static function order_again_link($order)
    {
        $order_id = $order->get_id();
        $customer_id = $order->get_customer_id();
        $user_id = get_current_user_id();
        $link = '';
        if ($user_id && $customer_id == $user_id) {
            if ($order) { //&& $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed' ) ) )
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = (int)$item->get_product_id(); // The product ID
                    $variation_id = (int)$item->get_variation_id(); // The variation ID
                    $product_type = get_post_type($product_id);
                    $product_status = get_post_status($product_id);
                    // Get the product SKU: Check that the product exist
                    if (($product_type === 'product' && $product_status == 'publish')) {
                        if ($variation_id === 0 ||
                            ($variation_id > 0 && get_post_status($variation_id) == 'publish' && get_post_type($variation_id) === 'product_variation')
                        ) {
                            // Get the WC_Product Object instance
                            $product = $item->get_product();

                            // Check if it is a valid WC_Product Object instance (and that the sku exist)
                            if (is_a($product, 'WC_Product')) {
                                $link = wp_nonce_url(add_query_arg('order_again', $order_id), 'woocommerce-order_again');
                                return $link;
                            }
                        }
                    }
                }

            }
        }

        return $link;
    }

    /**
     * Woocommerce Order again statuses
     */
    public
    static function order_again_statuses($array)
    {
        $array = array_merge($array, array('on-hold', 'processing', 'pending-payment', 'cancelled', 'refunded', 'authorized'));
        return $array;
    }

    /**
     * order_again_button_text
     */
    public
    static function order_again_button_text()
    {
        return __('Reorder', CPLINK_NAME);
    }

    /**
     * Save old order id to woocommerce session
     */
    public
    static function ordered_again($order_id)
    {
        WC()->session->set('reorder_from_order_id', $order_id);
        $notice = get_option('prro_cart_notice');
        if ($notice != '') {
            wc_add_notice($notice, 'notice');
        }
    }

    /**
     * Check cart, if empty reset the reorder flag in woocommerce session
     */
    public
    static function reset_session_flag()
    {
        WC()->session->set('reorder_from_order_id', '');
    }

    /**
     * Create a order note with link to the old order
     */
    public
    static function create_order_note($order_id)
    {
        $reorder_id = WC()->session->get('reorder_from_order_id');

        if ($reorder_id != '') {
            add_post_meta($order_id, '_reorder_from_id', $reorder_id, true);

            $order = wc_get_order($order_id);
            $url = get_edit_post_link($reorder_id);
            $note = sprintf(wp_kses(__('This is an reorder of order #<a href="%1s">%2s</a> <a href="#" class="order-preview" data-order-id="%3s" title="Vorschau"></a>. As a rule, customers can access items that have already been saved and linked to the selected delivery address when placing a "new order". Please note, however, that customers may have changed the number and quantity of items during the ordering process.', CPLINK_NAME), array('a' => array('href' => array(), 'class' => array(), 'data-order-id' => array()))), esc_url($url), $reorder_id, $reorder_id);
            $order->add_order_note(apply_filters('repeat_order_for_woocommerce_order_note', $note, $reorder_id, $order_id));
        }
        WC()->session->set('reorder_from_order_id', '');
    }

    public
    static function show_sage_orders()
    {
        global $wpdb;
        $paged = get_query_var('orders');
        $page = ($paged && is_numeric($paged)) ? absint($paged) : 1;
        $items_per_page = 10;
        $user_id = get_current_user_id();
        if (self::isset_return($_GET, 'user_id')) {
            $user_id = $_GET['user_id'];
        }

        $sales_orders = self::get_sage_orders($page, $items_per_page);
        $total = ceil( count( self::get_sage_orders(1, -1, 'id') ) / $items_per_page );
        //i_print($sales_orders);
        if (count($sales_orders)) {
            $sales_order_fields = array(
                'order_type' => 'Order type',
                'payment_type' => 'Payment type',
                'billto_address1' => 'Billing address 1',
                'billto_address2' => 'Billing address 2',
                'billto_zipcode' => 'Billing ZipCode',
                'billto_city' => 'Billing City',
                'billto_state' => 'Billing State',
                'shipto_address1' => 'Shipping address 1',
                'shipto_address2' => 'Shipping address 2',
                'shipto_zipcode' => 'Shipping ZipCode',
                'shipto_city' => 'Shipping City',
                'shipto_state' => 'Shipping State',
            );
            $order_line_fields = array(
                'item_code' => 'Item Code',
                'item_code_desc' => 'Description',
                'quantity' => 'Quantity',
                'unit_price' => 'Unit Price',
                'extension_amt' => 'Extension Amount',
            );
            $price_fields = array(
                'unit_price',
                'extension_amt',
            );

            $el_class = '';
            if( isset($_GET['sales_order_action']) )
                $el_class = 'scroll_to_me';
            echo '<h2 class="'.$el_class.'">' . __('Accepted Orders', CPLINK_NAME) . '</h2>';

            wc_get_template(
                'sage/sales-order/search-block.php', array('sales_orders' => $sales_orders)
            ); //require_once(CPLINK_PLUGIN_DIR . 'templates/woocommerce/sage/sales-order/search-block.php');

            wc_get_template(
                'sage/sales-order/sales-order.php', array('sales_orders' => $sales_orders)
            ); //require_once(CPLINK_PLUGIN_DIR . 'templates/woocommerce/sage/sales-order/sales-order.php');

            wc_get_template(
                'sage/pagination.php', array('base' => esc_url( wc_get_endpoint_url( 'orders' ) ), 'total' => $total, 'page' => $page, 'items_per_page' => $items_per_page)
            );
        }
    }

    public
    static function getSalesOrder($sales_order_no)
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;
        $orders_table = $table_prefix . 'sales_orders';
        $sql = "SELECT * FROM `$orders_table` WHERE `sales_order_no` = '$sales_order_no' ";

        $sales_orders = $wpdb->get_row($sql, ARRAY_A); //i_print($sales_orders);
        return $sales_orders;
    }

    public
    static function getSalesOrderLines($sales_order_no)
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;
        $orders_table = $table_prefix . 'sales_order_lines';

        $sql = "SELECT * FROM `$orders_table` WHERE `sales_order_no` = '$sales_order_no' ";

        $sales_order_lines = $wpdb->get_results($sql, ARRAY_A); //i_print($sales_order_lines);
        return $sales_order_lines;
    }

    public
    static function get_sage_orders($paged = 1, $items_per_page = 10, $select_what = '*', $key_as_key = false)
    {
        global $wpdb;
        if (!is_numeric($paged))
            $paged = 1;

        $offset = ($paged - 1) * $items_per_page;
        $table_prefix = self::$table_prefix;
        $ar_division_no = self::get_user_meta('cp_ar_division_no');
        $customer_no = self::get_user_meta('cp_customer_no');

        $orders_table = $table_prefix . 'sales_orders';

        $sql = "SELECT $select_what FROM `$orders_table` ";
        $where = " WHERE `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no')";

        if (isset($_GET['sales_order_action']) && $_GET['sales_order_action'] == 'search') {
            foreach ($_GET as $so_search_key => $so_search_val) {
                if (isset($so_search_val) && !empty($so_search_val)) {
                    switch ($so_search_key) {
                        case 'sales_order_no':
                        case 'web_sales_order_no':
                        case 'customer_po_no':
                            $where .= " AND `" . $so_search_key . "` LIKE '%" . $so_search_val . "%' ";
                            break;
                    }
                }
            }
        }

        $sql .= $where;

        $sql .= " ORDER BY `order_date` DESC, `sales_order_no` DESC";//echo $sql;
        if( $items_per_page > 0 )
            $sql .= " LIMIT $offset,$items_per_page";
//        echo $sql;

        $sales_orders = $wpdb->get_results($sql, ARRAY_A);

        //
        if( $key_as_key ){
            if( is_array($sales_orders) && count($sales_orders) ) {
                foreach ($sales_orders as $sales_order_key => $sales_order) {
                    $sales_orders[$sales_order_key] = $sales_orders[$sales_order_key][$select_what];
                }
            }
        }
        return $sales_orders;
    }

    public
    static function get_sage_order_line($sales_order_no)
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $order_lines_table = $table_prefix . 'sales_order_lines';
        $sql = "SELECT * FROM `$order_lines_table` WHERE `sales_order_no` = '$sales_order_no'"; //echo $sql;

        $sales_orders = $wpdb->get_results($sql, ARRAY_A);
        return $sales_orders;
    }

    public
    static function get_web_order_id($sales_order_id)
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;
        $web_order_id = '';

        $orders_table = $table_prefix . 'sales_orders';
        $sql = "SELECT * FROM `$orders_table` WHERE `sales_order_no` = '$sales_order_id'"; //echo $sql;

        $sales_order = $wpdb->get_row($sql, ARRAY_A);
        if ($sales_order && count($sales_order))
            $web_order_id = $sales_order['web_sales_order_no'];

        return $web_order_id;
    }

    public
    static function get_sage_invoices($paged = 1, $items_per_page = 10, $select_what = '*', $sql_where = '')
    {
        global $wpdb;
        if (!is_numeric($paged))
            $paged = 1;
        $offset = ($paged - 1) * $items_per_page;
        $table_prefix = self::$table_prefix;
        $ar_division_no = self::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_no = self::get_user_meta('cp_customer_no'); //'0000007'; //'ABF'; //

        $orders_table = $table_prefix . 'invoices';
        $invoice_lines_table = $table_prefix . 'invoice_lines';
        $invoice_serials_table = $table_prefix . 'invoice_serials';
        $sql = "SELECT $select_what FROM $orders_table ";
        $where = " WHERE `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no') ".$sql_where;

        if (isset($_GET['invoice_action']) && $_GET['invoice_action'] == 'search') {
            foreach ($_GET as $search_key => $search_val) {
                if (isset($search_val) && !empty($search_val)) {
                    switch ($search_key) {
                        case 'invoice_no':
                        case 'sales_order_no':
                        case 'customer_po_no':
                            $where .= " AND `" . $search_key . "` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'item_code':
                            $where .= " AND `invoice_no` IN (";
                            $where .= "SELECT $invoice_lines_table.`invoice_no` FROM $invoice_lines_table WHERE `" . $search_key . "` LIKE '%" . $search_val . "%'  ";
                            $where .= ")";
                            break;

                        case 'lot_serial_number':
                            $where .= " AND `invoice_no` IN (";
                            $where .= "SELECT $invoice_serials_table.`invoice_no` FROM $invoice_serials_table WHERE `" . $search_key . "` LIKE '%" . $search_val . "%'  ";
                            $where .= ")";
                            break;

                        //balance
                        case 'min_balance':
                            $search_val = 0 + $search_val;
                            $where .= " AND `balance` >= " . $search_val;

                            break;
                        case 'max_balance':
                            $search_val = 0 + $search_val;
                            $where .= " AND `balance` <= " . $search_val;
                            break;

                        //total
                        case 'min_total':
                            $search_val = 0 + $search_val;
                            $where .= " AND `total` >= " . $search_val;

                            break;
                        case 'max_total':
                            $search_val = 0 + $search_val;
                            $where .= " AND `total` <= " . $search_val;
                            break;
                    }
                }
            }
        }
        $sql .= $where;
        $sql .= " ORDER BY `invoice_date` DESC, `invoice_no` DESC";//echo $sql;
        if( $items_per_page > 0 )
            $sql .= " LIMIT $offset,$items_per_page";
        //echo $sql;

        $invoices = $wpdb->get_results($sql, ARRAY_A);
        return $invoices;
    }

    public
    static function get_sage_invoice($invoice_id, $col = '*')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $orders_table = $table_prefix . 'invoices';
        $sql = "SELECT $col FROM `$orders_table` WHERE `id` = '$invoice_id' "; //echo $sql;
        if (!current_user_can('administrator')) {
            $ar_division_no = self::get_user_meta('cp_ar_division_no');
            $customer_no = self::get_user_meta('cp_customer_no');
            $sql .= " AND `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no')";
        }

        $invoice = $wpdb->get_row($sql, ARRAY_A);
        if (empty($invoice))
            $invoice = array();

        return $invoice;
    }

    public
    static function get_sage_invoice_by($col_name = 'id', $val = '', $col = '*')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $orders_table = $table_prefix . 'invoices';
        $sql = "SELECT $col FROM `$orders_table` WHERE `$col_name` = '$val' "; //echo $sql;
        if (!current_user_can('administrator')) {
            $ar_division_no = self::get_user_meta('cp_ar_division_no');
            $customer_no = self::get_user_meta('cp_customer_no');
            $sql .= " AND `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no')";
        }

        $invoice = $wpdb->get_row($sql, ARRAY_A);
        if (empty($invoice))
            $invoice = array();

        return $invoice;
    }

    public
    static function get_order_invoices($sales_order_no)
    {
        global $wpdb;

        $table_prefix = self::$table_prefix;
        $ar_division_no = self::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_no = self::get_user_meta('cp_customer_no'); //'0000007'; //'ABF'; //

        $orders_table = $table_prefix . 'invoices';
        $sql = "SELECT * FROM `$orders_table` WHERE `sales_order_no` = $sales_order_no AND `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no')";
        $sql .= " ORDER BY `invoice_date` DESC";//echo $sql;

        $invoices = $wpdb->get_results($sql, ARRAY_A);
        return $invoices;
    }

    public
    static function get_sage_invoice_line($invoice_no, $header_seq_no = '')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $invoice_lines_table = $table_prefix . 'invoice_lines';
        $sql = "SELECT * FROM `$invoice_lines_table` WHERE `invoice_no` = '$invoice_no'"; //echo $sql;
        if ($header_seq_no)
            $sql .= " AND `header_seq_no` = '$header_seq_no'";

        $invoice_lines = $wpdb->get_results($sql, ARRAY_A);
        return $invoice_lines;
    }

    public
    static function get_sage_invoice_serials($invoice_no = '', $header_seq_no = '', $line_key = '', $col = '*')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $invoice_serials_table = $table_prefix . 'invoice_serials';
        $sql = "SELECT $col FROM `$invoice_serials_table` WHERE `invoice_no` = '$invoice_no' AND `header_seq_no` = '$header_seq_no' AND `line_key` = '$line_key'"; //echo $sql;

        $invoice_serials = $wpdb->get_row($sql, ARRAY_A);
        return $invoice_serials;
    }

    public
    static function get_sage_invoice_trackings($invoice_no = '', $header_seq_no = '', $line_key = '', $col = '*')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $invoice_trackings_table = $table_prefix . 'invoice_trackings';
        $sql = "SELECT $col FROM `$invoice_trackings_table` WHERE `invoice_no` = '$invoice_no' "; //echo $sql;

        if ($header_seq_no)
            $sql .= " AND `header_seq_no` = '$header_seq_no'";

        $invoice_trackings = $wpdb->get_results($sql, ARRAY_A);
        return $invoice_trackings;
    }

    public
    static function get_sage_item_stock($item_code = '', $warehouse_code = '')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;
        $col = 'quantity_available';
        $item_warehouses_table = $table_prefix . 'item_warehouses';
        $sql = "SELECT $col FROM `$item_warehouses_table` WHERE `item_code` = '$item_code' "; //echo $sql;
        $item_stock = 0;
        if ($warehouse_code) {
            $sql .= " AND `warehouse_code` = '$warehouse_code'";
            $item_stock = $wpdb->get_row($sql, ARRAY_A);
            if( count( $item_stock ) && isset($item_stock[$col]) )
                $item_stock = $item_stock[$col];
        } else {
            $items_stock = $wpdb->get_results($sql, ARRAY_A);
            if( count($items_stock) ){
                foreach ($items_stock as $item_stock_item){
                    if( count( $item_stock_item ) && isset($item_stock_item[$col]) )
                        $item_stock+= $item_stock_item[$col];
                }
            }
        }

        //quantity_available
        return $item_stock;
    }

    public
    static function get_sage_customer_price_level($ar_division_no = null, $customer_no = null, $item_code = '')
    {
        global $wpdb;
        if( !$item_code )
            return false;

        $table_prefix = self::$table_prefix;
        $customers_table = $table_prefix . 'customers';
        $price_codes_table = $table_prefix . 'price_codes';
        $items_table = $table_prefix . 'items';
        $pricelevels_by_customer_price_codes = $table_prefix . 'pricelevels_by_customer_price_codes';
        $col = 'price_level';

        $utc_date = $wpdb->get_row('SELECT UTC_TIMESTAMP() as `utc_date`', ARRAY_A);
        $utc_date = $utc_date['utc_date'];

        $price_level = ''; //sku = $item_code

        // Try to find price_level in customers table
        $sql = "SELECT `price_level` FROM  `$customers_table` WHERE `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no') "; //echo $sql;
        $customer_price_level = $wpdb->get_row($sql, ARRAY_A);
        if (!empty($customer_price_level)) {
            $price_level = $customer_price_level['price_level'];
        }

        // Next - we need to research also pricelevels_by_customer_price_codes table
        $sql = "SELECT product_line, price_code
                  FROM  `$items_table`
                  WHERE `item_code` = '$item_code'"; //echo $sql;
        $item = $wpdb->get_row($sql, ARRAY_A);

        $default_price_level = CPLINK::get_module_option('default_price_level');

        if (!empty($item) && $default_price_level == 'by_customer_price_code'){
            $product_line = $item['product_line'];
            $price_code = $item['price_code'];
            $sql = "SELECT `$col` FROM `$pricelevels_by_customer_price_codes` WHERE `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no') ";
            //$sql .= " AND effective_date < `$utc_date` AND end_date > '$utc_date'";
            $sql .= " AND product_line = '$product_line'";
            $sql .= " AND (price_code = '$price_code' OR price_code = '')";
            $sql .= " ORDER BY price_code DESC, end_date ASC";
            //echo $sql;

            $customer_price_level = $wpdb->get_row($sql, ARRAY_A);

            if( $customer_price_level && count( $customer_price_level ) && isset($customer_price_level[$col]) )
                $price_level = $customer_price_level[$col];
        }

        return $price_level;
    }

    public
    static function woo_product_price($price, $product, $qty = 1) // getSagePrice
    {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) )
            return $price;

        global $wpdb;
        $customer_no = '';
        $ar_division_no = '';
        $price_level = '';
        $current_date = date('Y-m-d H:i:s');
        $product_id = $product->get_id();
        $sku = $item_code = CPLINK::product_item_code($product_id); //echo '$item_code='.$item_code;

        $user_id = get_current_user_id();
        if ($user_id) {
            $ar_division_no = self::get_user_meta('cp_ar_division_no');
            $customer_no = self::get_user_meta('cp_customer_no'); //echo $ar_division_no .' --- '.$customer_no;
            $price_level = self::get_sage_customer_price_level($ar_division_no, $customer_no, $item_code);
        }

        $quantity = $qty;

        $sql = $wpdb->prepare(
            "CALL spPriceCalculation(%s,%s,%s,%s,%s,%s,@output)",
            array($sku, $ar_division_no, $customer_no, $price_level, $quantity, $current_date)
        ); //echo $sql;
        $result = $wpdb->get_row($sql, ARRAY_A);

        if( !empty( $result ) && isset($result['p_UnitPrice']) && !empty($result['p_UnitPrice']) )
            $price = $result['p_UnitPrice'];

        return $price;
    }

    public
    static function getSageTierPrices($product, $qty)
    {
        global $wpdb;
        $customer_no = '';
        $ar_division_no = '';
        $price_level = '';
        $_tierPrices = array();
        $current_date = date('Y-m-d H:i:s');
        $product_id = $product->get_id();
        $sku = $item_code = CPLINK::product_item_code($product_id); //echo '$item_code='.$item_code;

        $user_id = get_current_user_id();
        if ($user_id) {
            $ar_division_no = self::get_user_meta('cp_ar_division_no');
            $customer_no = self::get_user_meta('cp_customer_no');
            $price_level = self::get_sage_customer_price_level($ar_division_no, $customer_no, $item_code);
        }

        $quantity = 0;
        $sql = $wpdb->prepare(
            "CALL spPriceCalculation(%s,%s,%s,%s,%s,%s,@output)",
            array($sku, $ar_division_no, $customer_no, $price_level, $quantity, $current_date)
        );
        $result = $wpdb->get_row($sql, ARRAY_A);
        //i_print($result);
        if( !empty( $result ) && isset($result['p_UnitPrice']) && !empty($result['p_UnitPrice']) ){
            $maxQty = 99999999;
            $qtyBreak = 0;

            for ($i = 1; $i <= 5; $i++) {

                if ($qtyBreak == 0) {
                    if ($result['cBreakQuantity1'] == $maxQty) {
                        break;
                    }
                    $qtyBreak = 1;
                } else
                    $qtyBreak = $result['cBreakQuantity' . ($i - 1)] + 1;

                if ($result['cBreakQuantity' . $i] == $maxQty) {
                    $resultBreak_sql = $wpdb->prepare(
                        "CALL spPriceCalculation(%s,%s,%s,%s,%s,%s,@output)",
                        array($sku, $ar_division_no, $customer_no, $price_level, $qtyBreak, $current_date)
                    );
                    $rowBreak = $wpdb->get_row($resultBreak_sql, ARRAY_A);
                    if (!empty($rowBreak)) {
                        $rowBreak = $rowBreak[0];
                        if (!empty($rowBreak->p_UnitPrice)) {
                            if(self::validateTierPrice($qtyBreak, $_tierPrices)) {
                                $_tierPrices[] = array(
                                    'price_qty' => $qtyBreak,
                                    'price' => $rowBreak->p_UnitPrice,
                                    'formated_price' => wc_price($rowBreak->p_UnitPrice)
                                );
                            }
                        }
                    }
                    break;
                } else {
                    $resultBreak_sql = $wpdb->prepare(
                        "CALL spPriceCalculation(%s,%s,%s,%s,%s,%s,@output)",
                        array($sku, $ar_division_no, $customer_no, $price_level, $qtyBreak, $current_date)
                    );
                    $rowBreak = $wpdb->get_row($resultBreak_sql, ARRAY_A);
                    if (!empty($rowBreak)) {
                        $rowBreak = $rowBreak[0];

                        if (!empty($rowBreak->p_UnitPrice)) {
                            if(self::validateTierPrice($qtyBreak, $_tierPrices)) {
                                $_tierPrices[] = array(
                                    'price_qty' => $qtyBreak,
                                    'price' => $rowBreak->p_UnitPrice,
                                    'formated_price' => wc_price($rowBreak->p_UnitPrice)
                                );
                            }
                        }
                    }
                }
            }
        }

        return $_tierPrices;
    }

    public static function validateTierPrice($qtyBreak, $_tierPrices)
    {
        foreach($_tierPrices as $price) {
            if($price['price_qty'] == $qtyBreak) {
                return false;
            }
        }

        return true;
    }

    public
    static function woo_cart_product_subtotal($product_subtotal, $product, $quantity) // getSagePrice
    {
        return $product_subtotal;
        //return $quantity*self::woo_product_price($product_subtotal, $product, $quantity);
    }

    public
    static function woo_price_html($price, $product)
    {
        $product_price = $product->get_price();
        $customer_price = self::woo_product_price($price, $product);
        if( $customer_price < $product_price ) {
            $return = '<del aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi>'.wc_price($product_price).'</del>';
            $return.= '<ins><span class="woocommerce-Price-amount amount"><bdi>'.wc_price($customer_price).'</ins></pre>';
            return $return;
        }
        return $price;
    }

    public
    static function woo_cart_item_price($price, $cart_item, $cart_item_key)
    {
        $product = $cart_item['data']; //i_print($cart_item);
        $quantity = (isset($cart_item['quantity']))?$cart_item['quantity']:1;  //i_print($quantity);
        $product_price = $product->get_price();
        //$price = $product->get_price_html();
        $customer_price = self::woo_product_price($price, $product, $quantity);
        if( $customer_price < $product_price ) {
            $price = wc_price($customer_price);
        }
        return $price;
    }

    public
    static function woo_before_calculate_totals($cart_object ) // getSagePrice
    {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) )
            return;

        // $hash = cart item unique hash
        // $value = cart item data
        foreach ( $cart_object->get_cart() as $hash => $value ) {
            //i_print( $value['data']->get_price() );
            $p_price = $value['data']->get_price();
            $product = $value['data'];
            $quantity = $value['quantity'];

            $customer_price = self::woo_product_price($p_price, $product, $quantity);

            $value['data']->set_price($customer_price);
        }
    }

    public
    static function woo_available_payment_gateways( $available_gateways ){
        if( !is_admin() && $available_gateways )
            foreach ($available_gateways as $gateway_key => $gateway){
                if ( !self::isPaymentMethodAllowed($gateway_key) ) {
                    unset( $available_gateways[$gateway_key] );
                }
            }
        return $available_gateways;
    }
    public
    static function isPaymentMethodAllowed($paymentCode)
    {
        global $cp_modules_cf;
        $methodAllowed = true;

        $ar_division_no = self::get_user_meta('cp_ar_division_no');
        $customer_no = self::get_user_meta('cp_customer_no');
        $allowable_payment_methods = $cp_modules_cf['active_payment_methods_terms_list'];

        $sageCustomer = CPLINK::getCustomer($customer_no, $ar_division_no, 'id,terms_code, credit_hold');

        $custTermsCode = null;
        $custCreditHold = null;
        if($sageCustomer && $sageCustomer['id']) {
            $custTermsCode = $sageCustomer['terms_code'];
            $custCreditHold = $sageCustomer['credit_hold'];
        }
        //i_print($custTermsCode); i_print($custCreditHold);

        if($cp_modules_cf['cc_validation_by_credit_hold'] && $custCreditHold) {
            if(!in_array($paymentCode, $allowable_payment_methods)) {
                $methodAllowed = false;
            }
        } else if( $cp_modules_cf['cc_validation_by_terms_code'] ) {
            if(!empty($custTermsCode)) {
                $termsCodeList = $cp_modules_cf['cc_terms_list'];
                if(in_array($custTermsCode, $termsCodeList) && !in_array($paymentCode, $allowable_payment_methods)) { // QST question
                    $methodAllowed = false;
                }
            } else if(!in_array($paymentCode, $allowable_payment_methods)) {
                $methodAllowed = false;
            }
        }
        return $methodAllowed;
    }

    public
    static function product_item_code($product_id)
    {
        $cp_item_code = get_post_meta($product_id, '_cp_item_code', true);
        return $cp_item_code;
    }

    public
    static function getCustomer($customer_number, $ar_division_number, $fields = '*')
    {
        global $CP_Sage;
        $customer = $CP_Sage->getCustomers($customer_number, $ar_division_number, 0, 1, '-1', true, $fields);
        if( !empty($customer) && is_array($customer) ){
            return (array)$customer[0];
        }
        return array();
    }

    public
    static function get_user_meta($user_meta, $user_id = null)
    {
        if (!$user_id) {
            if (isset($_GET['user_id']) && $_GET['user_id']) {
                $user_id = $_GET['user_id'];
            } else {
                $user_id = get_current_user_id();
            }
        }
        if (!$user_id)
            return '';

        $user_data = self::$user_data;
        if( isset( $user_data[$user_meta] ) ){
            $return = $user_data[$user_meta];
        } else {
            $return = get_user_meta($user_id, $user_meta, true);
            self::$user_data[ $user_meta ] = $return;
        }
        return $return;
    }

    public
    static function save_order_to_queue($order_id)
    {
        if (!$order_id)
            return;

        global $wpdb;

        $table_prefix = CPLINK_DB_PREFIX;

        //Table structure for table `_queue`
        $table_name = $table_prefix . 'queue';

        $data = array(
            'web_sales_order_no' => $order_id,
            'message' => '',
            'export_count' => 0,
            'created_time' => wp_date("Y-m-d h:i:s"),
        );


        $sql = "SELECT `id` FROM `$table_name` WHERE `web_sales_order_no` = '$order_id'";

        $find_id = $wpdb->get_row($sql, ARRAY_A);
        if(!$find_id){
            $result = $wpdb->insert($table_name, $data);
        }

        //$result = $CP_Sage->insert_db($table_name, $data,array('web_sales_order_no'));
        /*i_print($result,true);
        exit;*/
    }

    //Actions

    public static function sendApiErrorMessageToClient($message, $request, $type)
    {
        global $cp_scope_cf, $cp_modules_cf;

        $msg = '';
        try {
            $template = 'elink_error_client_template';
            $sendTo = '';
            $attachment = false;
            $error_email_addr = $cp_scope_cf['error_email_addr'];
            switch ($type) {
                case 'orderExport':
                    $subject = 'Error On Order Export';
                    $sendTo = $cp_scope_cf['order_export_error_email'];
                    $template = $cp_scope_cf['order_export_error_template'] > 0 ? $cp_scope_cf['order_export_error_template'] : $template;
                    if ( $cp_scope_cf['order_export_error_send_attachment'] )
                        $attachment = true;
                    break;
                case 'billpayExport':
                    $subject = 'Error On Payment Export';
                    $sendTo = $cp_scope_cf['error_email_addr'];
                    $template = $cp_scope_cf['order_export_error_template'] > 0 ? $cp_scope_cf['order_export_error_template'] : $template;
                    break;
                case 'customersImport':
                case 'usersImport':
                    $subject = 'Error On Customers/Users Import';
                    $sendTo = $cp_scope_cf['customer_import_error_email'];
                    $template = $cp_scope_cf['customer_import_error_template'] > 0 ? $cp_scope_cf['customer_import_error_template'] : $template;
                    if ( $cp_scope_cf['customer_import_error_send_attachment'] )
                        $attachment = true;
                    break;
                case 'productsImport':
                    $subject = 'Error On Product Import';
                    $sendTo = $cp_scope_cf['product_import_error_email'];
                    $template = $cp_scope_cf['product_import_error_template'] > 0 ? $cp_scope_cf['product_import_error_template'] : $template;
                    if ( $cp_scope_cf['product_import_error_send_attachment'] )
                        $attachment = true;
                    break;
                default:
                    $subject = 'Global API ERROR';
                    $sendTo = $error_email_addr;
            }
            if( !$sendTo )
                $sendTo = $error_email_addr;
            if (strpos($template, 'elink_settings') !== false) { // 'Default' template is selected
                $template = 'elink_error_client_template';
            }

            if ($sendTo) {
                $admin_email = get_option('admin_email');
                $mailHtmlURL = CPLINK_PLUGIN_DIR . 'view/email/'.$template.'.html';
                $mailHtml = file_get_contents($mailHtmlURL);
                $message = str_replace(
                    array('{{Message}}', '{{Subject}}','{{URL}}'),
                    array($message, $subject, ''),
                    $mailHtml
                );

                /*$templateVars = [
                    'subject' => $subject,
                    'message' => $message,
                    'request' => $request
                ];*/
                $headers = array('Content-Type: text/html; charset=UTF-8',"From: ".$admin_email);
                if($request != '' && $attachment){
                    $message .= '<br><br>'.json_encode($request);
                }

                wp_mail($sendTo, $subject, $message,$headers);
            }
        } catch (\Exception $e) {
            //$msg = __($e->getMessage());
        }
        if ($msg) {
            //$this->_logger->addError('CertiProSolutions_Elink::sendApiErrorMessage: ' . $msg);
        }
    }

    //This function will appear on cplink_schedule_import cronjob
    public
    static function cplink_run_import( $import_module = '' )
    {
        global $CP_Sage;
        global $cp_modules_cf;
        //$cp_modules_cf = self::$module_options = get_option(CPLINK::get_settings_name('modules'), true);

        self::max_server_ini();

        $current_time = date('Y-m-d H:i:s');
        $last_import_date = get_option('clink_last_import_date', true);

        $import_schedules = self::$import_schedules;
        if( !isset( $import_schedules[$import_module] ) )
            return false;

        $import_rain = $import_schedules[$import_module];

        $CP_Sage->ItsCron();
        $import_module_sources = $import_rain['name'];
        $cplink_import_source = $import_rain['type'];

        $before_run = self::isset_return($import_rain, 'before_run', array());
        $after_run = self::isset_return($import_rain, 'after_run', array());
        $before_run[$import_module] = $import_module_sources;
        $run_imports = array_merge($before_run, $after_run);

        //i_print($run_imports); exit;

        if( count($run_imports) ){
            foreach ($run_imports as $run_module => $run_module_data){

                if( is_array($run_module_data) ){
                    $run_module_type = $run_module_data['name'];
                    $run_module_src = $run_module_data['type'];
                } else {
                    $run_module_type = $run_module_data;
                    $run_module_src = $cplink_import_source;
                }

                $import_module_enabled = self::isset_return($cp_modules_cf, $run_module.'_enabled', 1);
                if( $import_module_enabled ){
                    if( $run_module_src == 'purge' ){
                        $import_info = array(
                            'cplink_import_type' => $run_module_type,
                            'cplink_import_source' => 'sync'
                        );
                        $import_sync_result = CPLINK::cplink_import($import_info);
                    }
                    $import_info = array(
                        'cplink_import_type' => $run_module_type,
                        'cplink_import_source' => $run_module_src
                    );
                    $import_result = CPLINK::cplink_import($import_info);
                }
            }
        }

        update_option('clink_last_import_date', $current_time);
    }
    public
    static function requireCPLINKAdmin(){
        if( !class_exists('CPLINK_Admin') ){
            require_once( CPLINK_PLUGIN_DIR . 'lib/class.cplink-admin.php' );
        }
    }

    public
    static function order_queue_value( $order_id, $col = 'id' ){
        global $wpdb;
        $table_prefix = CPLINK_DB_PREFIX;
        $table_name = $table_prefix . 'queue';

        $sql = "SELECT `$col` FROM `$table_name` WHERE `web_sales_order_no` = '$order_id'";
        $val = $wpdb->get_row($sql, ARRAY_A);

        return self::isset_return($val, $col);
    }

    //This function will appear on cplink_schedule_export cronjob
    public
    static function cplink_run_export()
    {
        global $CP_Sage, $cp_scope_cf, $cp_modules_cf,$wpdb;
        $order_export_enabled = self::isset_return($cp_modules_cf, 'order_export_enabled');
        $max_exporting_attempts = self::isset_return($cp_modules_cf, 'max_exporting_attempts');
        $exportable_order_status = self::isset_return($cp_modules_cf, 'exportable_order_status');
        /*i_print($exportable_order_status);*/
        self::max_server_ini();

        self::requireCPLINKAdmin();

        if($order_export_enabled){
            $orders_to_export = array();
            $table_prefix = CPLINK_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';
            /*$sql_where = '';*/
            $sql_where = 'WHERE `active` = 1';
            if(is_numeric($max_exporting_attempts)  || !empty($exportable_order_status)){

                if(is_numeric($max_exporting_attempts)){
                    $sql_where .= ' AND `export_count` <= '.intval($max_exporting_attempts);
                }
                if(!empty($exportable_order_status)){
                    $array_string = implode("','",$exportable_order_status);
                    $sql_where .= " AND `status` IN ('".$array_string."')";
                }
                /*i_print($sql_where);
                exit;*/
            }
            $query = "SELECT `web_sales_order_no` FROM $table_name $sql_where ORDER BY `web_sales_order_no` DESC";
            $db_result = $wpdb->get_results($query,ARRAY_A);
            /*i_print($db_result);
                exit;*/
            if(!empty($db_result)){
                foreach ($db_result as $order){
                    $orders_to_export[] = $order['web_sales_order_no'];
                }
            }

            $export_orders_to_sage = CPLINK_Admin::export_orders_to_sage($orders_to_export);
        }
    }

    public static function getResponseCount( $req_info, $req_result ){
        global $CP_Sage;
        $response_n = 0;

        if( $req_info['success'] ){
            if( isset($req_info['counts']) ) {
                $request_counts = $req_info['counts']['request'];
                $req_status = CPLINK::isset_return($req_result, 'status');
                if ($req_status !== 0 && $req_result) { //echo 'here'.$request_counts;
                    $response_n = $request_counts;
                }
            }
        }
        return $response_n;
    }

    public static function cplink_import( $import_info )
    {
        CPLINK::max_server_ini();

        global $CP_Sage;
        $status = false;
        $err_msg = '<div> There is ERROR!!! </div>';

        $return = array(
            'status' => $status,
            'html' => $err_msg
        );

        $import_type = $import_info['cplink_import_type'];
        $import_source = $import_info['cplink_import_source'];
        $req_result = array();

        //Possible actions:
        //Global – Imports the whole module from Sage to eCommerce Portal.
        //Sync – Imports the changes of each module from Sage to eCommerce Portal.
        //Purge – Imports SQL database keys to compare if there are keys left in eCommerce Portal which
        // have been deleted in Sage then it removes them in eCommerce Portal as well

        $CP_Sage->setImportSource($import_source);
        $CP_Sage->setImportType($import_type);
        switch ($import_source) {
            case 'purge':
            case 'global':
                $modified_from = '-1';
                break;
            default:
                $modified_from = '';
                break;
        }

        $page = 1;
        $limit = 1000;
        $response_n = $limit;

        switch ($import_type) {
            case 'products':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getProducts(null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    if( $import_source != 'purge' && $response_n ) {
                        $insert_products = $CP_Sage->insertProducts($req_result);
                    }
                    $page++;
                }

                $status = true;
                $err_msg = 'All Products are imported successfully!';
                break;
            case 'productlines':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getProductLines(null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    //if( $response_n ) $insert_productLines = $CP_Sage->insertProductLines($req_result);
                    $page++;
                }

                $status = true;
                $err_msg = 'All Product Lines are imported successfully!';
                break;
            case 'inventory':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getInventories(null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    if( $import_source != 'purge' && $response_n )
                        $insert_inventories = $CP_Sage->insertInventories($req_result);
                    $page++;
                }

                $status = true;
                $err_msg = 'All Inventories are imported successfully!';
                break;
            case 'customers':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getCustomers(null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }

                $status = true;
                $err_msg = 'All Customers are imported successfully!';
                break;
            case 'customers_addresses':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getCustomersAddresses(null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }

                $status = true;
                $err_msg = 'All Customers Addresses are imported successfully!';
                break;
            case 'customers_taxexemptions':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getCustomersTaxExemptions(null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }

                $status = true;
                $err_msg = 'All Customers TaxExemptions are imported successfully!';
                break;
            case 'users':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getUsers(null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    if( $import_source != 'purge' && $response_n && $modified_from != '0' ){
                        $insert_users = $CP_Sage->insertUsers($req_result, $modified_from);
                    }
                    $page++;
                }

                $status = true;
                $err_msg = 'All Users are imported successfully!';
                break;
            case 'salesorders':
                if( !isset( $_POST['action'] ) || $_POST['action'] != 'cplink_import' )
                    $export_orders = self::cplink_run_export();

                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getSalesOrders(false, null, null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    //if( $response_n ) $insert_sales_orders = $CP_Sage->insertSalesOrders($req_result);
                    $page++;
                }

                $status = true;
                $err_msg = 'All Sales Orders are imported successfully!';
                break;
            case 'salesorders_history':
                //$export_orders = self::cplink_run_export(); //
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getSalesOrders(true, null, null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    //if( $response_n ) $insert_sales_orders = $CP_Sage->insertSalesOrders($req_result);
                    $page++;
                }

                $status = true;
                $err_msg = 'All Sales Orders are imported successfully!';
                break;
            case 'warehouses':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getWarehouses(null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    //if( $response_n ) $insert_products = $CP_Sage->insertWarehouses($customers);
                    $page++;
                }

                $status = true;
                $err_msg = 'All Warehouses are imported successfully!';
                break;
            case 'pricecodes':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getPricecodes($page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Price Codes are imported successfully!';
                break;
            case 'invoices':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getInvoices(false, null, null, null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Invoices are imported successfully!';
                break;
            case 'invoices_history':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getInvoices(true, null, null, null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Invoices are imported successfully!';
                break;
            case 'shippingmethods':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getShippingMethods(null,  $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Shipping Methods are imported successfully!';
                break;
            case 'paymenttypes':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getPaymentTypes(null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Payment Types are imported successfully!';
                break;
            case 'termscode':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getTermsCodes(null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Terms Codes are imported successfully!';
                break;
            case 'invoices_trackings':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getInvoicesTracking(null, null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Invoices Trackings are imported successfully!';
                break;
            case 'pricelevels_customerpricecodes':
                while ( $response_n == $limit ){
                    $req_result = $CP_Sage->getPriceLevelsByCustomerPriceCodes(null, $page, $limit, $modified_from);
                    $req_info = $CP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount( $req_info, $req_result );
                    $page++;
                }
                $status = true;
                $err_msg = 'All Price Levels are imported successfully!';
                break;
            default:
                $status = false;
                $err_msg = 'There is no any import action for your request!';
                break;
        }

        if ( in_array($import_source, $CP_Sage->getStoreWhenSource() ) ) {
            $CP_Sage->PurgeData();
        }

        $req_status = CPLINK::isset_return($req_result, 'status');
        $api_error = $CP_Sage->getAPIError();
        if( $api_error['errorNo'] ){
            $return = array(
                'status' => 2,
                'html' => 'API ERROR: '.$api_error['errorMessage']
            );

            CPLINK::sendApiErrorMessageToClient( $api_error['errorMessage'], '' , $import_type.'Import');
        } else {
            if ($req_status === 0) {
                $return = array(
                    'status' => 0,
                    'html' => CPLINK::isset_return($req_result, 'message')
                );
                CPLINK::sendApiErrorMessageToClient( 'Request Failed. Please check your API. | <b>'.$import_type.' Import</b>', '' , $import_type.'Import');
            } else {
                $return = array(
                    'status' => $status,
                    'html' => $err_msg
                );
            }
        }

        $return['last_import_data'] = $CP_Sage->getLastImportData();

        return $return;
    }

    //ETC
    public
    static function array2csv(array &$array, $file_path)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen($file_path, 'w');
        //fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }

    public
    static function get_attachment_by_post_name($post_name)
    {
        $args = array(
            'posts_per_page' => 1,
            'post_type' => 'attachment',
            'name' => trim($post_name),
        );

        $get_attachment = new WP_Query($args);

        if (!$get_attachment || !isset($get_attachment->posts, $get_attachment->posts[0])) {
            return false;
        }

        return $get_attachment->posts[0];
    }

    public
    static function media_sideload_image($file, $post_id = 0, $desc = null, $return = 'html')
    {
        if (!empty($file)) {

            $allowed_extensions = array('jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp');

            $allowed_extensions = apply_filters('image_sideload_extensions', $allowed_extensions, $file);
            $allowed_extensions = array_map('preg_quote', $allowed_extensions);

            // Set variables for storage, fix file filename for query strings.
            preg_match('/[^\?]+\.(' . implode('|', $allowed_extensions) . ')\b/i', $file, $matches);

            if (!$matches) {
                return new WP_Error('image_sideload_failed', __('Invalid image URL.', CPLINK_NAME));
            }

            $file_array = array();
            $file_array['name'] = wp_basename($matches[0]);

            // Download file to temp location.
            $file_array['tmp_name'] = CPLINK::download_url($file);

            // If error storing temporarily, return the error.
            if (is_wp_error($file_array['tmp_name'])) {
                return $file_array['tmp_name'];
            }

            // Do the validation and storage stuff.
            $id = media_handle_sideload($file_array, $post_id, $desc);

            // If error storing permanently, unlink.
            if (is_wp_error($id)) {
                @unlink($file_array['tmp_name']);
                return $id;
            }

            // Store the original attachment source in meta.
            add_post_meta($id, '_source_url', $file);

            // If attachment ID was requested, return it.
            if ('id' === $return) {
                return $id;
            }

            $src = wp_get_attachment_url($id);
        }

        // Finally, check to make sure the file has been saved, then return the HTML.
        if (!empty($src)) {
            if ('src' === $return) {
                return $src;
            }

            $alt = isset($desc) ? esc_attr($desc) : '';
            $html = "<img src='$src' alt='$alt' />";

            return $html;
        } else {
            return new WP_Error('image_sideload_failed');
        }
    }

    public static function download_url($url, $timeout = 300)
    {
        // WARNING: The file is not automatically deleted, the script must unlink() the file.
        if (!$url) {
            return new WP_Error('http_no_url', __('Invalid URL Provided.', CPLINK_NAME));
        }

        $url_filename = basename(parse_url($url, PHP_URL_PATH));

        $tmpfname = wp_tempnam($url_filename);
        if (!$tmpfname) {
            return new WP_Error('http_no_file', __('Could not create temporary file.', CPLINK_NAME));
        }

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => $timeout,
                'stream' => true,
                'filename' => $tmpfname,
                'sslverify' => false
            )
        );

        if (is_wp_error($response)) {
            unlink($tmpfname);
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if (200 !== $response_code) {
            $data = array(
                'code' => $response_code,
            );

            // Retrieve a sample of the response body for debugging purposes.
            $tmpf = fopen($tmpfname, 'rb');

            if ($tmpf) {
                $response_size = apply_filters('download_url_error_max_body_size', KB_IN_BYTES);

                $data['body'] = fread($tmpf, $response_size);
                fclose($tmpf);
            }

            unlink($tmpfname);

            return new WP_Error('http_404', trim(wp_remote_retrieve_response_message($response)), $data);
        }

        $content_md5 = wp_remote_retrieve_header($response, 'content-md5');

        if ($content_md5) {
            $md5_check = verify_file_md5($tmpfname, $content_md5);

            if (is_wp_error($md5_check)) {
                unlink($tmpfname);
                return $md5_check;
            }
        }

        return $tmpfname;
    }

    public static function woocommerce_registration_custom_fields_beggining(){
        global $cp_modules_cf;

        $sage_account_info = self::isset_return($cp_modules_cf, 'sage_account_info');
        if($sage_account_info == 'optional'){
            if(isset($_POST['register'])) {
                $class_to_trigger = '.cp_choose_form_type';
                if(isset($_POST['has_cp_account'])){
                    $class_to_trigger .= '.have_account';
                }
                ?>
                <script>
                    jQuery(document).ready(function($){
                        setTimeout(function(){
                            console.log($('<?php echo $class_to_trigger; ?>'));
                            $('<?php echo $class_to_trigger; ?>').click();

                        },500);
                    });
                </script>
                <?php
            }
            ?>
            <div class="buttons_wrapper">
                <button type="button" class="cp_choose_form_type have_account"><?php _e('I have an existing account',CPLINK_NAME)?></button>
                <button type="button" class="cp_choose_form_type"><?php _e("I don't have an existing account",CPLINK_NAME)?></button>
            </div>
        <?php }else{
            ?>
            <script>
                jQuery(document).ready(function($){
                    $('.woocommerce-form-register').addClass('show_fields');
                });
            </script>
            <?php
        } ?>

        <h3><?php _e('Personal Information',CPLINK_NAME); ?></h3>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_billing_first_name"><?php _e( 'First Name', CPLINK_NAME ); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
        </p>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_billing_last_name"><?php _e( 'Last Name', CPLINK_NAME ); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
        </p>

        <?php if($sage_account_info == 'optional' || $sage_account_info == 'required'){ ?>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_cp_customer_no"><?php _e( 'Customer Number', CPLINK_NAME ); ?> <span class="required">*</span></label>
                <input type="text" class="input-text" name="cp_customer_no" id="reg_cp_customer_no" value="<?php if ( ! empty( $_POST['cp_customer_no'] ) ) esc_attr_e( $_POST['cp_customer_no'] ); ?>" />
                <input type="hidden" class="input-text" name="has_cp_account" id="reg_has_account" value="has_account" />
            </p>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_billing_postcode"><?php _e( 'Zip/Postal Code', CPLINK_NAME ); ?> <span class="required">*</span></label>
                <input type="text" class="input-text" name="billing_postcode" id="reg_billing_postcode" value="<?php if ( ! empty( $_POST['billing_postcode'] ) ) esc_attr_e( $_POST['billing_postcode'] ); ?>" />
            </p>

        <?php } ?>
        <h3><?php _e('Sign-in Information',CPLINK_NAME); ?></h3>

        <?php
    }
    public static function woocommerce_registration_custom_fields_end(){
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_password2"><?php _e( 'Confirm password', CPLINK_NAME ); ?> <span class="required">*</span></label>
            <input type="password" class="input-text" name="password2" id="reg_password2" value="" />
        </p>
        <?php
    }

    public static function woocommerce_registration_custom_fields_errors( $errors, $username, $email ) {

        /*global $cp_modules_cf;
        i_print($cp_modules_cf);*/

        if ( isset( $_POST['billing_first_name'] ) && empty( $_POST['billing_first_name'] ) ) {
            $errors->add( 'billing_first_name_error', __( ' First name is required!', CPLINK_NAME ) );
        }
        if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
            $errors->add( 'billing_last_name_error', __( ' Last name is required!.', CPLINK_NAME ) );
        }
        if(isset($_POST['has_cp_account'])){
            if ( isset( $_POST['cp_customer_no'] ) && empty( $_POST['cp_customer_no'] ) ) {
                $errors->add( 'cp_customer_no_error', __( ' Customer number is required!.', CPLINK_NAME ) );
            }
            if ( isset( $_POST['billing_postcode'] ) && empty( $_POST['billing_postcode'] ) ) {
                $errors->add( 'billing_postcode_error', __( ' Zip/Postal code is required!.', CPLINK_NAME ) );
            }
        }
        if( isset( $_POST['password'] ) ){
            if( !empty($_POST['password']) && $_POST['password'] != $_POST['password2']){
                $errors->add( 'reg_password2_error', __( ' Passwords do not match.', CPLINK_NAME ) );
            }
        }


        if(isset($_POST['has_cp_account']) && count($errors->errors) <= 0){
            if ( isset( $_POST['cp_customer_no'] ) && !empty( $_POST['cp_customer_no'] ) && isset( $_POST['billing_postcode'] ) && !empty( $_POST['billing_postcode'] ) ) {
                $cp_customer_no = $_POST['cp_customer_no'];
                $billing_postcode = $_POST['billing_postcode'];
                global $wpdb;
                $table_prefix = self::$table_prefix;
                $customers_table = $table_prefix . 'customers';
                $sql = "SELECT * FROM `$customers_table` WHERE LOWER(`customer_no`) = LOWER('$cp_customer_no') AND `zip_code` LIKE '$billing_postcode%' ";

                $customers_list = $wpdb->get_row($sql, ARRAY_A);

                //i_print($customers_list);
                if(empty($customers_list)){
                    $errors->add( 'has_cp_account_error', __( " We can't save the customer. Invalid 'Customer Number' or 'Zip/Postal Code'", CPLINK_NAME ) );
                }
            }
        }
        //$errors->add( 'in_any_case_error', __( ' In Any Case', CPLINK_NAME ) );

        return $errors;
    }

    public static function woocommerce_registration_custom_fields_save_from_front( $customer_id ){
        $cp_customer_no = $_POST['cp_customer_no'];
        $billing_postcode = $_POST['billing_postcode'];

        $first_name = sanitize_text_field($_POST['billing_first_name']);
        $last_name = sanitize_text_field($_POST['billing_last_name']);

        $has_cp_account = false;
        if(isset($_POST['has_cp_account'])){
            $has_cp_account = true;
        }

        if (isset($first_name)) {
            update_user_meta($customer_id, 'first_name', $first_name);
        }

        if (isset($last_name)) {
            update_user_meta($customer_id, 'last_name', $last_name);
        }

        self::woocommerce_registration_custom_fields_save($customer_id,$cp_customer_no,'',true,$has_cp_account,$first_name,$last_name,$billing_postcode);
    }

    public static function woocommerce_registration_custom_fields_save( $customer_id,$cp_customer_no,$cp_division_no = '',$is_front = false,$has_cp_account = true,$first_name = '',$last_name = '',$billing_postcode = '',$user_shipto_code = '' )
    {
        global $cp_modules_cf;
        global $wpdb;
        $create_address_on_sync = $cp_modules_cf['create_address_on_sync'];
        $update_address_on_sync = $cp_modules_cf['update_address_on_sync'];
        /*$customers_sync_field_on_update = $cp_modules_cf['customers_sync_field_on_update'];*/
        $address_default_country = $cp_modules_cf['address_default_country'];
        $default_telephone = $cp_modules_cf['default_telephone'];


        if(!$is_front){
            $table_prefix = self::$table_prefix;
            $customers_table = $table_prefix . 'customers';
            $sql = "SELECT `zip_code` FROM `$customers_table` WHERE LOWER(`customer_no`) = LOWER('$cp_customer_no') AND `ar_division_no` = '$cp_division_no' ";
            $customers_list = $wpdb->get_row($sql, ARRAY_A);

            $billing_postcode = self::isset_return($customers_list, 'zip_code');
        }

        if (isset($first_name) && trim($first_name) != '') {
            update_user_meta($customer_id, 'billing_first_name', $first_name);
            update_user_meta($customer_id, 'shipping_first_name', $first_name);
        }
        if (isset($last_name) && trim($last_name) != '') {
            update_user_meta($customer_id, 'billing_last_name', $last_name);
            update_user_meta($customer_id, 'shipping_last_name', $last_name);
        }
        if (isset($billing_postcode) && !$has_cp_account) {
            update_user_meta($customer_id, 'billing_postcode', sanitize_text_field($billing_postcode));
            update_user_meta($customer_id, 'shipping_postcode', sanitize_text_field($billing_postcode));
        }


        if($has_cp_account ){
            $add_new_addresses = self::isset_return($cp_modules_cf, 'add_new_addresses');
            if($is_front && $add_new_addresses == '0'){
                return;
            }

            if ( isset( $cp_customer_no ) && !empty( $cp_customer_no ) && isset( $billing_postcode ) && !empty( $billing_postcode ) ) {
                $table_prefix = self::$table_prefix;
                $customers_table = $table_prefix . 'customers';
                $sql = "SELECT * FROM `$customers_table` WHERE LOWER(`customer_no`) = LOWER('$cp_customer_no') AND `zip_code` LIKE '$billing_postcode%' ";

                $customers_list = $wpdb->get_row($sql, ARRAY_A);
                $ar_division_no = $customers_list['ar_division_no'];
                $customer_no = $customers_list['customer_no'];
                update_user_meta($customer_id, 'cp_ar_division_no', $ar_division_no);
                update_user_meta($customer_id, 'cp_customer_no', $customer_no);

                if(!empty($customers_list)) {
                    $address_line1 = $customers_list['address_line1'];
                    $address_line2 = $customers_list['address_line2'];
                    $country_code = $customers_list['country_code'];
                    $city = $customers_list['city'];
                    $state = $customers_list['state'];
                    $telephone_no = $customers_list['telephone_no'];
                    $customer_name = $customers_list['customer_name'];
                    $zip_code = $customers_list['zip_code'];
                    $email_address = $customers_list['email_address'];
                    $primary_shipto_code = $customers_list['primary_shipto_code'];

                    if (!$is_front && trim($user_shipto_code) != '') {
                        $primary_shipto_code = $user_shipto_code;
                    }

                    if (empty(trim($country_code))) {
                        $country_code = $address_default_country;
                    }

                    if (empty(trim($telephone_no))) {
                        $telephone_no = $default_telephone;
                    }

                    $billing_address_1_check = get_user_meta($customer_id, 'billing_address_1', true);
                    if (($create_address_on_sync && empty($billing_address_1_check)) || ($update_address_on_sync && !empty($billing_address_1_check))) {
                        //update_user_meta($customer_id, 'billing_company', $customer_name);
                        //update_user_meta($customer_id, 'cp_primary_shipto_code', $primary_shipto_code);
                        update_user_meta($customer_id, 'billing_postcode', $zip_code);
                        update_user_meta($customer_id, 'billing_email', $email_address);

                        update_user_meta($customer_id, 'billing_country', self::get_country_iso2($country_code));
                        update_user_meta($customer_id, 'billing_address_1', $address_line1);
                        update_user_meta($customer_id, 'billing_address_2', $address_line2);
                        update_user_meta($customer_id, 'billing_city', $city);
                        update_user_meta($customer_id, 'billing_state', $state);
                        update_user_meta($customer_id, 'billing_phone', $telephone_no);
                    }

                    $customers_shipto_table = $table_prefix . 'customers_shipto';
                    $sql = "SELECT * FROM `$customers_shipto_table` WHERE LOWER(`customer_no`) = LOWER('$cp_customer_no') AND `ar_division_no` = '$ar_division_no' ";
                    /*if(is_numeric($primary_shipto_code)){
                        $sql .= "AND `shipto_code` = '$primary_shipto_code'";
                    }*/
                    $customers_shipto_list = $wpdb->get_results($sql, ARRAY_A);

                    $shipping_address_1_check = get_user_meta($customer_id, 'shipping_address_1', true);
                    if(($create_address_on_sync && empty($shipping_address_1_check)) || ($update_address_on_sync && !empty($shipping_address_1_check))){
                        if (!empty($customers_shipto_list)) {
                            $count_prefix = 2;
                            $all_shipping_prefixs = array();
                            $is_first = true;
                            foreach ($customers_shipto_list as $key => $value) {
                                $shipping_address_line1 = $value['address1'];
                                $shipping_address_line2 = $value['address2'];
                                $shipping_country_code = $value['country_code'];
                                $shipping_city = $value['city'];
                                $shipping_state = $value['state'];
                                $shipping_telephone_no = $value['telephone_no'];
                                $shipping_customer_name = $value['name'];
                                $shipping_zip_code = $value['zip_code'];
                                $shipping_email_address = $value['email_address'];
                                $shipping_shipto_code = $value['shipto_code'];
                                $warehouse_code = $value['warehouse_code'];

                                $meta_key_prefix = 'shipping' . $count_prefix;

                                if (empty(trim($shipping_telephone_no))) {
                                    $shipping_telephone_no = $default_telephone;
                                }

                                if (empty(trim($shipping_country_code))) {
                                    $shipping_country_code = $address_default_country;
                                }
                                if (trim($primary_shipto_code) != '') {
                                    if ($primary_shipto_code == $shipping_shipto_code) {
                                        $meta_key_prefix = 'shipping';
                                        update_user_meta($customer_id, 'cp_warehouse_code', $warehouse_code);
                                        $count_prefix--;
                                    }
                                } else {
                                    if ($is_first) {
                                        $meta_key_prefix = 'shipping';
                                        $count_prefix--;
                                    }
                                }

                                update_user_meta($customer_id, $meta_key_prefix . '_cp_shipto_code', $shipping_shipto_code);
                                //update_user_meta($customer_id, $meta_key_prefix . '_company', $shipping_customer_name);
                                update_user_meta($customer_id, $meta_key_prefix . '_postcode', $shipping_zip_code);
                                update_user_meta($customer_id, $meta_key_prefix . '_email', $shipping_email_address);

                                update_user_meta($customer_id, $meta_key_prefix . '_country', self::get_country_iso2($shipping_country_code));
                                update_user_meta($customer_id, $meta_key_prefix . '_address_1', $shipping_address_line1);
                                update_user_meta($customer_id, $meta_key_prefix . '_address_2', $shipping_address_line2);
                                update_user_meta($customer_id, $meta_key_prefix . '_city', $shipping_city);
                                update_user_meta($customer_id, $meta_key_prefix . '_state', $shipping_state);
                                update_user_meta($customer_id, $meta_key_prefix . '_phone', $shipping_telephone_no);


                                update_user_meta($customer_id, $meta_key_prefix . '_last_name', $last_name);
                                update_user_meta($customer_id, $meta_key_prefix . '_first_name', $first_name);
                                array_push($all_shipping_prefixs, $meta_key_prefix);
                                $count_prefix++;
                                $is_first = false;
                            }
                            update_user_meta($customer_id, 'wc_address_book_shipping', $all_shipping_prefixs);
                        }
                    }

                }
            }

        }

    }

    public static function add_shipping_address_custom_field( $address_fields ) {
        if ( ! isset( $address_fields['shipping_cp_shipto_code'] ) ) {
            $address_fields['shipping_cp_shipto_code'] = array(
                'label'        => '',
                'required'     => false,
                'type'     => 'hidden',
                'priority'     => -1,
            );
        }

        return $address_fields;
    }

    public static function cp_custom_address_format($formats){
        $formats['default'] = $formats['default'] . "\n{cp_shipto_code}";
        $formats['us'] = $formats['default'] . "\n{cp_shipto_code}";
        /*i_print($formats['default']);
        exit;*/
        return $formats;
    }
    public static function cp_users_can_register($allow_reg){
        global $cp_modules_cf;
        $enable_registration = self::isset_return($cp_modules_cf, 'enable_registration');
        if($enable_registration == '0'){
            $allow_reg = 'no';
        }else{
            $allow_reg = 'yes';
        }

        return $allow_reg;
    }
    public static function cp_users_allow_generate_password($allow_reg){
        return 'no';
    }
    public static function cp_users_allow_signup_and_login_from_checkout($allow_reg){
        return 'no';
    }
    public static function cp_users_enable_checkout_login_reminder($allow_reg){
        return 'no';
    }
    public static function disable_lost_pass()
    {
        if ( is_admin() ) {
            $userdata = wp_get_current_user();
            $user = new WP_User($userdata->ID);
            if ( !empty( $user->roles ) && is_array( $user->roles ) && $user->roles[0] == 'administrator' )
                return true;
        }

        global $cp_modules_cf;
        $enable_forgot_password = self::isset_return($cp_modules_cf, 'enable_forgot_password');
        if($enable_forgot_password){
            return true;
        }
        return false;
    }

    public static function remove_lost_pass($text)
    {
        global $cp_modules_cf;
        $enable_forgot_password = self::isset_return($cp_modules_cf, 'enable_forgot_password');
        if($enable_forgot_password){
            return $text;
        }
        return str_replace( array('Lost your password?', 'Lost your password'), '', trim($text, '?') );
    }

    public static function cp_can_user_login($username){

        // First need to get the user object
        $user = get_user_by('login', $username);
        if(!$user) {
            $user = get_user_by('email', $username);
            if(!$user) {
                return $username;
            }
        }


        global $cp_modules_cf;
        $require_sage_account_no = self::isset_return($cp_modules_cf, 'require_sage_account_no');
        $require_warehouse_code = self::isset_return($cp_modules_cf, 'require_warehouse_code');

        if($require_sage_account_no){
            $cp_ar_division_no = get_user_meta($user->ID, 'cp_ar_division_no', true);
            $cp_customer_no = get_user_meta($user->ID, 'cp_customer_no', true);

            //for testing $userStatus = 1;
            $login_page  = home_url('/login/');
            if(trim($cp_ar_division_no) == '' || trim($cp_customer_no) == ''){
                wp_redirect("?login=failed");
                exit;
            }

        }
        if($require_warehouse_code){
            $cp_warehouse_code = get_user_meta($user->ID, 'cp_warehouse_code', true);
            if(trim($cp_warehouse_code) == ''){
                wp_redirect("?login=failed");
                exit;
            }
        }

    }

    public static function cp_forcelogin(){
        $modules_options = get_option(self::get_settings_name('modules'), true);
        $require_login = self::isset_return($modules_options, 'require_login');

        if($require_login) {
            $url = self::cp_getUrl();

            $require_login_whitelist = self::isset_return($modules_options, 'require_login_whitelist');
            $require_login_whitelist = explode(',',$require_login_whitelist);
            array_push($require_login_whitelist,'/my-account','/my-account/');
            if (!is_user_logged_in()) {
                $whitelist = apply_filters('cp_forcelogin_whitelist', $require_login_whitelist);
                $redirect_url = apply_filters('cp_forcelogin_redirect', $url);
                /*i_print(in_array($_SERVER['REQUEST_URI'], $whitelist),true);
            if(!in_array($_SERVER['REQUEST_URI'], $whitelist)) {
                i_print($require_login_whitelist);
                i_print($_SERVER['REQUEST_URI']);
            }
            exit;*/
                //preg_replace('/\?.*/', '', $url) != preg_replace('/\?.*/', '', wp_login_url()) &&
                if (!in_array($_SERVER['REQUEST_URI'], $whitelist)) {
                    wp_safe_redirect($redirect_url);
                    exit();
                }
            }
        }
    }
    public static function cp_getUrl() {
        $url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
        $url .= '://' . $_SERVER['SERVER_NAME'];
        $url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];
        $url .= '/my-account';
        return $url;
    }

    public static function custom_thank_you_page($order_id){
        global $cp_modules_cf;
        $sales_order_redirect_to_pending_orders = self::isset_return($cp_modules_cf, 'sales_order_redirect_to_pending_orders');
        if($sales_order_redirect_to_pending_orders){
            $order = wc_get_order( $order_id );
            $url = wc_get_account_endpoint_url( 'orders' );
            if ( ! $order->has_status( 'failed' ) ) {
                wp_safe_redirect( $url );
                exit;
            }
        }
    }
}