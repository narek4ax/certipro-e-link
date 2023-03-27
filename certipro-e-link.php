<?php
/*
 * Plugin Name: CertiPro E-Link
 * Plugin URI:
 * Description: Sage 100 + WordPress Integrations. CertiPro’s E-Link integration offers a secure and fully automated solution to your eCommerce business.
 * Author: NGA HUB
 * Version: 0.0.2
 * Author URI:
 * Requires at least: 5.8
 * Tested up to: 6.0.2
 * Requires PHP: 7.1
 * WC requires at least: 3.9
 * WC tested up to: 6.8.2
*/

// Our prefix is CPLINK / cplink
global $wpdb;
define( 'CPLINK_PLUGIN_NAME', 'CertiPro E-Link' );
define( 'CPLINKVersion', '0.0.2' );
define( 'CPLINK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CPLINK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPLINK_PROTECTION_H', plugin_basename(__FILE__) );
define( 'CPLINK_NAME', 'cpe-link' );
define( 'CPLINK_DB_VERSION', '0.1' );
define( 'CPLINK_DB_PREFIX', $wpdb->prefix . 'cplink_' );
define( 'CPLINK_SETTINGS_NAME', 'cplink-settings' );


define( 'CPLINK_SETTINGS_LINK', 'certipro-e-link' );

if( !function_exists('i_print') ){
    function i_print( $array, $dump = false ) {
        if( isset($_GET['json_encode']) ){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($array);
            return ;
        }
        echo '<pre>';
        if( !$dump ){
            print_r( $array );
        } else {
            var_dump($array);
        }
        echo '</pre>';
    }
}

//schedules cron jobs
add_filter( 'cron_schedules', array( 'CPLINK', 'cplink_cron_schedules'), 10, 1 );

register_activation_hook( __FILE__, array( 'CPLINK', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'CPLINK', 'plugin_deactivation' ) );

require_once( CPLINK_PLUGIN_DIR . 'lib/class.cplink.php' );
require_once( CPLINK_PLUGIN_DIR . 'lib/class.cp-sage.php' );
global $CP_Sage, $cp_scope_cf, $cp_modules_cf, $cp_shipping_methods;

add_action( 'init', array( 'CPLINK', 'init' ) );

add_action('init', function() { //if( CPLINK::is_woo_active() ) {
    global $CP_Sage;
    if(isset($_GET['cp_action']) ){
        if( $_GET['cp_action'] == 'procedure_emulate' ) {
            require_once(CPLINK_PLUGIN_DIR . 'lib/data/PriceCalculationProcedure.php');
            exit;
        }
        if( $_GET['cp_action'] == 'insert_users' ) {
            $users = $CP_Sage->getUsers(null, 0, -1, '-1', true);
            //i_print($users);
            $CP_Sage->insertUsers($users);
            exit;
        }
        if( $_GET['cp_action'] == 'sales_orders' ) {
            $sales_orders = $CP_Sage->getSalesorders(false, null, null, null, 0, -1, '-1', false);
            i_print($sales_orders);
            exit;
        }
        if( $_GET['cp_action'] == 'isPaymentMethodAllowed' ) {
            $user_id = get_current_user_id();
            $is_allowed = CPLINK::isPaymentMethodAllowed('sagepaymentsusaapi', $user_id);
            i_print($is_allowed);
            exit;
        }
        if( $_GET['cp_action'] == 'run_export' ) {
            $is_allowed = CPLINK::cplink_run_export();
            //i_print($is_allowed);
        }
        if( $_GET['cp_action'] == 'run_import' ) {
            CPLINK::cplink_run_import( 'products' );exit;
        }
    }

    if( isset($_GET['get_remote_image']) ){
        set_time_limit(0);
        $image = wp_remote_get( 'http://eimages.valtim.com/acme-images/product/m/h/mh01-black_main.jpg' );
        i_print($image); exit;
    }
}, 999);

if ( is_admin() ) {
    require_once( CPLINK_PLUGIN_DIR . 'lib/class.cplink-admin.php' );
    add_action( 'init', array( 'CPLINK_Admin', 'init' ) );
    //add_action('acf/init', array( 'CPLINK', 'my_acf_op_init' ) );
}
//require_once( CPLINK_PLUGIN_DIR . 'lib/acf-fields.php' );


/*require_once CPLINK_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker = PucFactory::buildUpdateChecker(
    'https://bitbucket.org/CPLINK/CPLINK-updates/raw/master/info.json',
    __FILE__,
    'wiser-yoast-updates'
);*/
