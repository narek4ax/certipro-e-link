<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */
global $current_user;
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$allowed_html = array(
    'a' => array(
        'href' => array(),
    ),
    'span'=> array(
            'class' => array()
    )
);
?>

    <div class="dashboard_hi_div">
        <?php
        //esc_html( $current_user->display_name )
        printf(
        /* translators: 1: user display name 2: logout url */
            wp_kses( __( 'Hello %1$s <span class="logout_span "> (not %3$s? <a href="%2$s">Log out</a>) </span>', 'woocommerce' ), $allowed_html ),
            '<strong>' . esc_html( $current_user->first_name.' '.$current_user->last_name.' ('.esc_html( $current_user->user_email ).')'  ) . '</strong>',
            esc_url( wc_logout_url() ), esc_html( $current_user->display_name )
        );


        echo '<div class="edit_account_link_div">';
        echo '<a href="'.wc_customer_edit_account_url().'" class="i_cp_link">'.__('Edit Account details').'</a>';
        echo '</div>';
        ?>
    </div>
<?php

$cp_view_balance = CPLINK::get_user_meta('cp_view_balance');
if( $cp_view_balance ){
    $ar_division_no = CPLINK::get_user_meta('cp_ar_division_no');
    $customer_no = CPLINK::get_user_meta('cp_customer_no');

    $sageCustomer = CPLINK::getCustomer($customer_no, $ar_division_no, $fields = '*');
    if( trim($sageCustomer['current_balance']) == '' )
        $sageCustomer['current_balance'] = 0;
    if( $sageCustomer ){
        echo '<div class="cp_account_balance_wrapper"> <header> <h3>'.__('Account Balance', CPLINK_NAME).'</h3> </header>';
        echo '<div>'.__('Your current account balance is:', CPLINK_NAME).' <strong>$'.$sageCustomer['current_balance'].'</strong></div>';
        echo '</div>';
    }
}

?>
    <p>
        <?php
        /* translators: 1: Orders URL 2: Address URL 3: Account URL. */
        $dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">billing address</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' );
        if ( wc_shipping_enabled() ) {
            /* translators: 1: Orders URL 2: Addresses URL 3: Account URL. */
            $dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' );
        }
        printf(
            wp_kses( $dashboard_desc, $allowed_html ),
            esc_url( wc_get_endpoint_url( 'orders' ) ),
            esc_url( wc_get_endpoint_url( 'edit-address' ) ),
            esc_url( wc_get_endpoint_url( 'edit-account' ) )
        );
        ?>
    </p>

<?php
/**
 * My Account dashboard.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_account_dashboard' );

/**
 * Deprecated woocommerce_before_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_before_my_account' );

/**
 * Deprecated woocommerce_after_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_after_my_account' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
