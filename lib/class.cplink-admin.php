<?php

class CPLINK_Admin
{

    private static $initiated = false;
    private static $settings_name = '';

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        self::$initiated = true;

        if (!is_network_admin())
            self::register_cplink_settings();

        add_action('admin_menu', array('CPLINK_Admin', 'init_menus'), 10, 2);
        add_action('admin_notices', array('CPLINK_Admin', 'general_admin_notice'));
        add_action('wp_ajax_cplink_import', array('CPLINK_Admin', 'cplink_import'));
        add_action('wp_ajax_i_export_orders_to_sage_request', array('CPLINK_Admin', 'export_orders_to_sage_request'));
        add_action('wp_ajax_i_delete_queue_orders', array('CPLINK_Admin', 'delete_queue_orders'));
        add_action('wp_ajax_i_import_order_to_queue', array('CPLINK_Admin', 'import_order_to_queue'));
        add_action('admin_enqueue_scripts', array('CPLINK_Admin', 'enqueue_admin_scripts'));

        add_action('show_user_profile', array('CPLINK_Admin', 'cp_show_extra_profile_fields'));
        add_action('edit_user_profile', array('CPLINK_Admin', 'cp_show_extra_profile_fields'));
        add_action('user_profile_update_errors', array('CPLINK_Admin', 'cp_user_profile_update_errors'), 10, 3);
        add_action('personal_options_update', array('CPLINK_Admin', 'cp_update_profile_fields'));
        add_action('edit_user_profile_update', array('CPLINK_Admin', 'cp_update_profile_fields'));

        add_filter('woocommerce_product_data_tabs', array('CPLINK_Admin', 'cp_sage_product_data_tab'));
        add_filter('woocommerce_product_data_panels', array('CPLINK_Admin', 'cp_sage_product_tab_content'));
        add_action('woocommerce_process_product_meta_simple', array('CPLINK_Admin', 'cp_sage_product_option_fields'));
        add_action('woocommerce_process_product_meta_variable', array('CPLINK_Admin', 'cp_sage_product_option_fields'));

        add_action('add_meta_boxes', array('CPLINK_Admin', 'cplink_woo_metaboxes'), 10, 2);
        add_action('save_post', array('CPLINK_Admin', 'cp_metaboxes_save'), 10, 2);
    }

    public static function general_admin_notice()
    {
        if (!CPLINK::is_woo_active()) {
            echo '<div class="notice notice-warning is-dismissible"><h3>' . CPLINK_PLUGIN_NAME . '</h3>
             <p>' . __('Woocommerce plugin is missing, Please install Woocommerce & try again', CPLINK_NAME) . '</p>
         </div>';
        }
    }

    public static function init_menus()
    {
        $parent_slug = CPLINK_SETTINGS_LINK;
        $icon_link = CPLINK_PLUGIN_URL . 'images/icon/CertiProIcon-white.png';
        add_menu_page('CertiPro E-Link', 'CertiPro E-Link', 'manage_options', $parent_slug, array('CPLINK_Admin', 'i_settings'), $icon_link, '80.08');
        add_submenu_page($parent_slug, 'Import', 'Import', 'manage_options', 'cplink_import', array('CPLINK_Admin', 'cplink_import_page'), 1);
        add_submenu_page($parent_slug, 'Queue', 'Queue', 'manage_options', 'cplink_queue', array('CPLINK_Admin', 'cplink_queue_page'), 2);
    }

    public static function enqueue_admin_scripts()
    {
        wp_enqueue_style('cplink_admin_global_style', CPLINK_PLUGIN_URL . 'resources/style/admin_global.css', array(), CPLINKVersion, 'all');
    }

    public static function cplink_css_and_js($enqueue_uploader = false)
    {
        wp_enqueue_style('cplink_bootstrap_style', CPLINK_PLUGIN_URL . 'resources/style/bootstrap/css/bootstrap.min.css', array(), CPLINKVersion, 'all');
//        wp_enqueue_script('cplink_moment_js', CPLINK_PLUGIN_URL . 'resources/style/bootstrap/js/moment.min.js', array('jquery'), CPLINKVersion, true );
        wp_enqueue_style('cplink_ui_style', CPLINK_PLUGIN_URL . 'resources/js/datepicker/jquery-ui.css', array(), CPLINKVersion, 'all');
        wp_enqueue_script('cplink_ui_js', CPLINK_PLUGIN_URL . 'resources/js/datepicker/jquery-ui.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_style('cplink_datatable_style', CPLINK_PLUGIN_URL . 'resources/js/datatable/jquery.dataTables.min.css', array(), CPLINKVersion, 'all');
        wp_enqueue_style('cplink_dataTables_dateTime_style', CPLINK_PLUGIN_URL . 'resources/js/datatable/dataTables.dateTime.min.css', array(), CPLINKVersion, 'all');
        wp_enqueue_script('cplink_datatable_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/jquery.dataTables.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_datatable_moment_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/moment.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_dataTables_dateTime_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/dataTables.dateTime.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink_datatable_colReorder_js', CPLINK_PLUGIN_URL . 'resources/js/datatable/dataTables.colReorder.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_style('cplink_admin_style', CPLINK_PLUGIN_URL . 'resources/style/admin_style.css', array(), CPLINKVersion, 'all');
        wp_enqueue_script('cplink-validate-js', CPLINK_PLUGIN_URL . 'resources/js/js_validate/jquery.validate.min.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_script('cplink-admin-js', CPLINK_PLUGIN_URL . 'resources/js/admin_js.js', array('jquery'), CPLINKVersion, true);
        wp_localize_script('cplink-admin-js', 'cplink_infos',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'loadingMessage' => __('Loading...', CPLINK_NAME),
                'importMessage' => __('Import in progress...', CPLINK_NAME),
                'exportMessage' => __('Export in progress...', CPLINK_NAME),
                'exportSuccessMessage' => __('Export is successfully done!', CPLINK_NAME),
                'exportErrorMessage' => __('Something went wrong:', CPLINK_NAME),
                'deleteMessage' => __('Delete in progress...', CPLINK_NAME),
                'deleteSuccessMessage' => __('Delete is successfully done!', CPLINK_NAME),
                'deleteErrorMessage' => __('Something went wrong!', CPLINK_NAME),
            )
        );
    }

    public static function i_settings()
    {
        global $cplink_options;

        $settings_name = self::get_settings_name();

        $cplink_options = get_option($settings_name, true);

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();

        wp_enqueue_script('cplink_options_js', CPLINK_PLUGIN_URL . 'resources/plugin-options/js.js', array('wp-color-picker', 'jquery-ui-core', 'jquery-ui-datepicker'), false, true);
        wp_enqueue_style('cplink_options_style', CPLINK_PLUGIN_URL . 'resources/plugin-options/style.css', array(), CPLINKVersion, 'all');

        self::cplink_css_and_js();
        //wp_enqueue_script( 'cplink-admin-js', CPLINK_PLUGIN_URL.'resources/js/admin_js.js' , array('jquery'), CPLINKVersion, true );

        require_once(CPLINK_PLUGIN_DIR . 'view/admin/cplink_settings.php');
    }

    public static function cplink_import_page()
    {

        self::cplink_css_and_js(true);
        wp_enqueue_script('cplink-import-js', CPLINK_PLUGIN_URL . 'resources/js/cp_import.js', array('jquery'), CPLINKVersion, true);

        require_once(CPLINK_PLUGIN_DIR . 'view/admin/cplink_import.php');
    }

    public static function cplink_queue_page()
    {

        self::cplink_css_and_js(true);
        //wp_enqueue_script( 'cplink-admin-js', CPLINK_PLUGIN_URL.'resources/js/admin_js.js' , array('jquery'), CPLINKVersion, true );

        require_once(CPLINK_PLUGIN_DIR . 'view/admin/cplink_queue.php');
    }

    /**
     * CPLINK ACF Actions Shortcode
     * @static
     */
    public static function cplink_shortcode_example($atts = array())
    {
        extract(shortcode_atts(array(
            'id' => '',
        ), $atts));

        $html = '<div class="cplink_actions_div">';

        $html .= '</div>';

        return $html;
    }


    public static function cplink_option_name($field, $key = false)
    {
        $op_name = self::$settings_name;

        $name = $field['id'];

        if (isset($field['global_option']) && $field['global_option'])
            return $name;

        if ($key)
            return $op_name . '[' . $name . '][' . $key . ']';

        return $op_name . '[' . $name . ']';
    }

    public static function cplink_option_name_1($field, $key = false)
    {
        $name = $field['id'];

        if (isset($field['global_option']) && $field['global_option'])
            return $name;

        if ($key)
            return CPLINK_SETTINGS_NAME . '[' . $name . '][' . $key . ']';

        return CPLINK_SETTINGS_NAME . '[' . $name . ']';
    }

    /*
     * Extra Profile Fields
     * */
    public static function cp_show_extra_profile_fields($user)
    {
        $cp_ar_division_no = get_the_author_meta('cp_ar_division_no', $user->ID);
        $cp_customer_no = get_the_author_meta('cp_customer_no', $user->ID);
        $inactive_user = get_the_author_meta('cp_inactive_user', $user->ID);
        $view_balance = get_the_author_meta('cp_view_balance', $user->ID);
        $view_invoice = get_the_author_meta('cp_view_invoice', $user->ID);
        //Add SAGE user ID
        ?>
        <h3><?php esc_html_e('E-Link options', CPLINK_NAME); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="cp_ar_division_no"><?php esc_html_e('AR Division Number', CPLINK_NAME); ?></label></th>
                <td>
                    <input type="text" id="cp_ar_division_no" name="cp_ar_division_no"
                           value="<?php echo esc_attr($cp_ar_division_no); ?>" class="regular-text"/>
                </td>
            </tr>
            <tr>
                <th><label for="cp_customer_no"><?php esc_html_e('Customer Number', CPLINK_NAME); ?></label></th>
                <td>
                    <input type="text" id="cp_customer_no" name="cp_customer_no"
                           value="<?php echo esc_attr($cp_customer_no); ?>" class="regular-text"/>
                </td>
            </tr>
            <tr>
                <th><label for="cp_inactive_user"><?php esc_html_e('Inactive User', CPLINK_NAME); ?></label></th>
                <td>
                    <input type="checkbox" id="cp_inactive_user" name="cp_inactive_user"
                        <?php if($inactive_user)echo 'checked="checked"'; ?> />
                </td>
            </tr>
            <tr>
                <th><label for="cp_view_balance"><?php esc_html_e('View Balance', CPLINK_NAME); ?></label></th>
                <td>
                    <input type="checkbox" id="cp_view_balance" name="cp_view_balance"
                           <?php if($view_balance)echo 'checked="checked"'; ?> />
                </td>
            </tr>
            <tr>
                <th><label for="cp_view_invoice"><?php esc_html_e('View Invoice', CPLINK_NAME); ?></label></th>
                <td>
                    <input type="checkbox" id="cp_view_invoice" name="cp_view_invoice"
                        <?php if($view_invoice)echo 'checked="checked"'; ?> />
                </td>
            </tr>
        </table>
        <?php
    }

    public static function cp_user_profile_update_errors($errors, $update, $user)
    {
        /*if ( empty( $_POST['cp_ar_division_no'] ) ) {
            $errors->add( 'cp_ar_division_no_error', __( '<strong>ERROR</strong>: Please enter ...', CPLINK_NAME ) );
        }*/
    }

    public static function cp_update_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        $update_fields = array(
            'cp_ar_division_no',
            'cp_customer_no',
        );
        $on_off_fields = array(
            'cp_inactive_user',
            'cp_view_balance',
            'cp_view_invoice'
        );
        $update_fields = array_merge($update_fields, $on_off_fields);

        foreach ($update_fields as $update_field_key) {
            if ( isset($_POST[$update_field_key]) ) {
                update_user_meta($user_id, $update_field_key, $_POST[$update_field_key]);
            } elseif( in_array($update_field_key, $on_off_fields) ) {
                update_user_meta($user_id, $update_field_key, '');
            }
        }
    }

    /*
     * Register CPLink Settings
     * */

    public static function set_settings_name($current_tab = 'general')
    {
        $settings_name = CPLINK_SETTINGS_NAME;

        $current_tab = (isset($_REQUEST['tab'])) ? $_REQUEST['tab'] : $current_tab;

        if ($current_tab == 'general')
            $current_tab = '';

        if ($current_tab)
            $settings_name .= '-' . $current_tab;

        self::$settings_name = $settings_name;
    }

    public static function get_settings_name()
    {
        return self::$settings_name;
    }

    private static function register_cplink_settings()
    {
        self::set_settings_name();
        $settings_name = self::get_settings_name();

        register_setting('cplink_option_settings', $settings_name, array('CPLINK_Admin', 'settings_validate'));
    }

    public static function settings_validate($input)
    {
        global $CP_Sage;
        // add_settings_error( $setting, $code, $message, $type )
        $message = 'Settings saved.';
        $type = 'updated';
        add_settings_error('cplink_settings', 'cplink_settings_updated', $message, $type); //exit;
        if ( isset($CP_Sage) ) {
            $CP_Sage->testConnection();
        }
        return $input;
    }

    /*
     * Woo Functionality
     */
    public static function get_product_data_fields()
    {

        $cp_product_data_fields = array(
            '_cp_tax_class' => array(
                'type' => 'text',
                'label' => __('Tax Class', 'woocommerce'),
                'desc_tip' => 'true',
                'description' => __('Enter ...', 'woocommerce')
            ),
            '_cp_warranty_code' => array(
                'type' => 'text',
                'label' => __('Warranty Code', 'woocommerce'),
                'desc_tip' => 'true',
                'description' => __('Enter ...', 'woocommerce')
            ),
            '_cp_commission_rate' => array(
                'type' => 'text',
                'label' => __('Commission Rate', 'woocommerce'),
                'desc_tip' => 'true',
                'description' => __('Enter ...', 'woocommerce')
            ),
            '_cp_sale_method' => array(
                'type' => 'text',
                'label' => __('Sale Method', 'woocommerce'),
                'desc_tip' => 'true',
                'description' => __('Enter ...', 'woocommerce')
            ),
        );

        return $cp_product_data_fields;
    }

    public static function cp_sage_product_data_tab($tabs)
    {
        $tabs['cp_sage'] = array(
            'label' => __('E-Link Information', 'woocommerce'),
            'target' => 'cp_sage_product_options',
            //'class'		=> array( 'show_if_simple', 'show_if_variable'  ),
            'priority' => 90,
        );
        return $tabs;
    }

    public static function cp_sage_product_tab_content()
    {
        global $post;

        echo '<div id="cp_sage_product_options" class="panel woocommerce_options_panel">';
        echo '<div class="options_group">';

        $cp_product_data_fields = self::get_product_data_fields();

        foreach ($cp_product_data_fields as $cp_product_data_id => $cp_product_data_field) {

            woocommerce_wp_text_input(array(
                'id' => $cp_product_data_id,
                'label' => $cp_product_data_field['label'],
                'desc_tip' => $cp_product_data_field['desc_tip'],
                'description' => $cp_product_data_field['description'],
                'type' => $cp_product_data_field['type']
            ));
        }

        /* woocommerce_wp_text_input( array(
             'id'				=> '_cp_item_code',
             'label'				=> __( 'Gift card validity (in days)', 'woocommerce' ),
             'desc_tip'			=> 'true',
             'description'		=> __( 'Enter the number of days the gift card is valid for.', 'woocommerce' ),
             'type' 				=> 'number',
             'custom_attributes'	=> array(
                 'min'	=> '1',
                 'step'	=> '1',
             ),
         ) );*/

        echo '</div>';
        echo '</div>';
    }

    public static function cp_sage_product_option_fields($post_id)
    {
        $cp_product_data_fields = self::get_product_data_fields();
        foreach ($cp_product_data_fields as $cp_product_data_id => $cp_product_data_field) {
            if (isset($_POST[$cp_product_data_id])) :
                update_post_meta($post_id, $cp_product_data_id, $_POST[$cp_product_data_id]);
            endif;
        }

    }

    /*
     *	Meta boxes
     */

    public static function cplink_woo_metaboxes($post_type, $post)
    {
        add_meta_box(
            'cplink_woo_order_details',       // $id
            'E-Link Information',                  // $title
            array('CPLINK_Admin', 'cplink_woo_order_details'),  // $callback
            'shop_order',                 // $page
            'normal',                  // $context
            'high'                     // $priority
        );
//        add_meta_box(
//            'cplink_woo_order_additional_information',
//            'Additional Information',
//            array( 'CPLINK_Admin', 'cplink_woo_order_additional_information' ),
//            'shop_order',
//            'normal',
//            'high'
//        );
    }
    public static function cp_metaboxes_save($post_id){
        if ( get_post_type($post_id) == 'shop_order' ){
            if(isset($_POST['cp_ar_division_no'])){
                update_post_meta($post_id,'cp_ar_division_no',$_POST['cp_ar_division_no']);
            }
            if(isset($_POST['cp_customer_no'])){
                update_post_meta($post_id,'cp_customer_no',$_POST['cp_customer_no']);
            }
            if(isset($_POST['cp_po_number'])){
                update_post_meta($post_id,'cp_po_number',$_POST['cp_po_number']);
            }
            if(isset($_POST['cp_comment'])){
                update_post_meta($post_id,'cp_comment',$_POST['cp_comment']);
            }
            if(isset($_POST['cp_sales_order_number'])){
                update_post_meta($post_id,'cp_sales_order_number',$_POST['cp_sales_order_number']);
            }
        }

    }

    public static function cplink_woo_order_details($post)
    {
        //self::cplink_css_and_js(true);
        $post_id = $post->ID;
        $cp_sales_order_number = get_post_meta($post_id, 'cp_sales_order_number', true);

        $order = $post;
        if (!is_a($order, 'WC_Order')) {
            $order_id = $order->ID;

            // Get an instance of the WC_Order object
            $order = wc_get_order($order_id);
        }
        $user_id = $order->get_user_id();

        /*if($user_id) {
            $cp_ar_division_no = get_the_author_meta('cp_ar_division_no', $user_id);
            $cp_customer_no = get_the_author_meta('cp_customer_no', $user_id);
        }else{
        }*/
        $cp_ar_division_no = get_post_meta($order_id, 'cp_ar_division_no', true);
        $cp_customer_no = get_post_meta($order_id, 'cp_customer_no', true);
        $cp_po_number = get_post_meta($order_id, 'cp_po_number', true);
        $cp_comment = get_post_meta($order_id, 'cp_comment', true);
        //Add SAGE user ID

        global $wpdb;
        $table_prefix = CPLINK_DB_PREFIX;

        //Table structure for table `_queue`
        $table_name = $table_prefix . 'queue';
        $db_result = $wpdb->get_results("SELECT * FROM $table_name WHERE web_sales_order_no = $order_id");

        $_sageresult = get_post_meta($order_id, '_sageresult', true);
        $_sage_result_array = get_post_meta($order_id, '_sage_result_array', true);
        if( isset( $_GET['cp_debug'] ) ) {
            i_print($_sageresult);
            i_print($_sage_result_array);
            i_print($order->get_transaction_id() . ' here should be transaction id');
        }

        ?>
        <script>
            jQuery(document).ready(function ($) {
                var ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
                $('.import_to_queue').on('click', function (event) {
                    event.preventDefault()
                    var clickedButton = $(this);
                    var orderId = clickedButton.attr('data-order-id');
                    console.log(orderId);
                    if ($.isNumeric(orderId)) {
                        $.ajax({
                            url: ajax_url,
                            type: 'POST',
                            dataType: "json",
                            data: {
                                'action': 'i_import_order_to_queue',
                                'order_id': orderId
                            },
                            beforeSend: function (xhr) {

                            },
                            success: function (data) {
                                console.log(data);
                                var buttonParent = clickedButton.parent();
                                if (data.success) {
                                    buttonParent.html('<div class="ajax_result alert alert-success">' + data.html + '</div>');
                                    setTimeout(function () {
                                        $('.ajax_result').parents('tr').remove();
                                    }, 5000);
                                } else {
                                    buttonParent.append('<div class="ajax_result alert alert-danger">' + data.html + '</div>');
                                    setTimeout(function () {
                                        $('.ajax_result.alert-danger').remove();
                                    }, 3000);
                                }
                            }
                        });
                    }
                });
            });
        </script>
        <div class="order_export_information_section">
            <h3><?php esc_html_e('Export Information', CPLINK_NAME); ?></h3>

            <table class="form-table">
                <tr>
                    <th><label for="cp_sales_order_id"><?php esc_html_e('Order Number', CPLINK_NAME); ?></label></th>
                    <td>
                        <span id="cp_sales_order_id"><?php echo esc_attr($order_id); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="cp_ar_division_no"><?php esc_html_e('Division Number', CPLINK_NAME); ?></label></th>
                    <td>
                        <input type="text" id="cp_ar_division_no" name="cp_ar_division_no"
                               value="<?php echo esc_attr($cp_ar_division_no); ?>" class="regular-text"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="cp_customer_no"><?php esc_html_e('Customer Number', CPLINK_NAME); ?></label></th>
                    <td>
                        <input type="text" id="cp_customer_no" name="cp_customer_no"
                               value="<?php echo esc_attr($cp_customer_no); ?>" class="regular-text"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="cp_sales_order_number"><?php esc_html_e('Sage Order Number', CPLINK_NAME); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cp_sales_order_number"  name="cp_sales_order_number"
                               value="<?php echo esc_attr($cp_sales_order_number); ?>" class="regular-text"/>
                    </td>
                </tr>
                <?php if (!empty($db_result) && !$db_result[0]->active): ?>
                    <tr>
                        <th><label><?php esc_html_e('Missing From Queue', CPLINK_NAME); ?></label></th>
                        <td>
                            <button type="button" data-order-id="<?php echo $order_id; ?>"
                                    class="import_to_queue button button-primary"><?php esc_html_e('Save and Add to Queue', CPLINK_NAME); ?></button>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="order_additional_information_section">
            <h3><?php esc_html_e('Additional Information', CPLINK_NAME); ?></h3>

            <table class="form-table">
                <tr>
                    <th><label for="cp_po_number"><?php esc_html_e('PO Number', CPLINK_NAME); ?></label></th>
                    <td>
                        <input type="text" id="cp_po_number" name="cp_po_number"
                               value="<?php echo esc_attr($cp_po_number); ?>" class="regular-text"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="cp_comment"><?php esc_html_e('Comment', CPLINK_NAME); ?></label></th>
                    <td>
                        <textarea type="text" id="cp_comment" name="cp_comment"
                                  class="regular-text"><?php echo esc_attr($cp_comment); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }




//    public static function cplink_woo_order_additional_information($post)
//    {
//        self::cplink_css_and_js(true);
//        $post_id = $post->ID;
//
//    }


    /*
     *	Field generators
     */
    public static function create_section_for_text($field, $value = '')
    {
        if (!$value && CPLINK::isset_return($field, 'default')) $value = $field['default'];
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cplink_option_name($field, $field['id_key']) . '" value="' . htmlspecialchars($value) . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_email($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (CPLINK::isset_return($field, 'validate'))
            $attrs .= ' validate ';
        if (!$value && CPLINK::isset_return($field, 'default')) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cplink_option_name($field, $field['id_key']) . '" value="' . htmlspecialchars($value) . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input email_field" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_number($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value && isset($field['default'])) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="number" name="' . self::cplink_option_name($field, $field['id_key']) . '" value="' . $value . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_textarea($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value && CPLINK::isset_return($field, 'default')) $value = $field['default'];
        $rows = ($field['rows']) ? $field['rows'] : 3;
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<textarea type="text" rows="' . $rows . '" name="' . self::cplink_option_name($field) . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input" >' . $value . '</textarea>';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_textarea_editor($field, $value = '')
    {
        $html = '';
        echo '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        echo '<p class="subtitle">' . $field['subtitle'] . '</p>';
        wp_editor($value, 'field_' . $field['id'] . '_' . $field['id_key'],
            array(
                'textarea_rows' => 12,
                'textarea_name' => self::cplink_option_name($field),
                //'media_buttons' => 1,
            )
        );
        /*echo '<textarea type="text" name="'.self::cplink_option_name( $field ).'" ' .
            ' id="field_'.$field['id'].'_'.$field['id_key'].'" placeholder="' . CPLINK::isset_return($field, 'placeholder').'" class="i_input i_texteditor" >'. $value . '</textarea>';*/
        echo '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_checkbox($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $checked = '';
        if ($value) $checked = 'checked';
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="checkbox" name="' . self::cplink_option_name($field) . '" value="1" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_checkbox" ' . $checked . ' >';
        $html .= '<span class="description">' . $field['description'] . '</span>';

        return $html;
    }

    public static function create_section_for_radio($field, $options)
    {
        $html = '';
        return $html;
    }

    public static function create_section_for_selectbox($field, $value = '')
    {
        $options = ($field['options']) ? $field['options'] : array();
        $is_multiple = (isset($field['multiple'])) ? $field['multiple'] : false;
        $html = '';
        $html .= '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';

        if ($value == '' && CPLINK::isset_return($field, 'default')) $value = $field['default'];

        $attrs = '';
        $field_name = self::cplink_option_name($field);

        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if ($is_multiple) {
            $attrs .= 'multiple';
            $field_name .= '[]';
        }
        $html .= '<select name="' . $field_name . '" id="field_' . $field['id'] . '_' . $field['id_key'] . '" ' . $attrs . ' ' . $attrs . ' >';
        //$html.= '<option value="null" > --- </option>';
        if (count($options)) {
            foreach ($options as $option => $option_name) {
                $i_selected = '';
                if ($is_multiple && is_array($value)) {
                    if (in_array($option, $value))
                        $i_selected = 'selected';
                } elseif ($option == $value) {
                    $i_selected = 'selected';
                }
                $html .= '<option value="' . $option . '" ' . $i_selected . '  >' . $option_name . '</option>';
            }
        }
        $html .= '</select>';

        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_post_selectbox($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $posts = get_pages(array('numberposts' => -1, 'post_type' => 'page', 'post_parent' => 0));
        $front_page_elements = $value;

        if (empty($front_page_elements) || !count($front_page_elements)) {
            $front_page_elements = array('null');
        }
        //i_print($value);

        $html = '';
        $html .= '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label> <br>';

        $html .= '<ul id="featured_posts_list">';

        foreach ($front_page_elements as $element) {
            $html .= '<li class="featured_post_ex"><select name="' . self::cplink_option_name($field) . '[]" id="field_' . $field['id'] . '_' . $field['id_key'] . '" ' . $attrs . ' >';
            $html .= '<option value="null" > --- </option>';
            foreach ($posts as $post) {
                $i_selected = '';
                if ($element == $post->ID) $i_selected = 'selected';
                $html .= '<option value="' . $post->ID . '" ' . $i_selected . '  >' . $post->post_title . '</option>';
            }
            $html .= '</select><span class="dashicons dashicons-sort i_dragicon" title="Drag for sorting"></span> ';
            $html .= '<p class=""><a href="#" class="i_remove_feature_post"><span class="dashicons dashicons-no"></span> Remove</a></p></li>';
        }

        $html .= '</ul>';
        $html .= '<a href="#" class="i_add_featured_post"><span class="dashicons dashicons-plus"></span>Add featured post</a>';
        //$html.= '<input type="hidden" id="i_the_max_id" value="'.$element_counter.'" />';

        return $html;
    }

    public static function create_section_for_post_selector($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $posts = get_pages(array('numberposts' => -1, 'post_type' => 'page', 'post_parent' => 0));
        //i_print($value);

        $html = '';
        $html .= '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label> <br>';

        $html .= '<select name="' . self::cplink_option_name($field) . '" id="field_' . $field['id'] . '_' . $field['id_key'] . '" ' . $attrs . ' >';
        $html .= '<option value="" > --- </option>';
        foreach ($posts as $post) {
            $i_selected = '';
            if ($value == $post->ID) $i_selected = 'selected';
            $html .= '<option value="' . $post->ID . '" ' . $i_selected . '  >' . $post->post_title . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public static function create_section_for_image_url($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $class = '';
        if (trim($value) == '') $class = 'i_hidden';
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cplink_option_name($field, $field['id_key']) . '" value="' . $value . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input i_input_url upload_image_button" >';
        $html .= '<img src="' . $value . '" class="i_preview_img i_preview_field_' . $field['id'] . '_' . $field['id_key'] . ' ' . $class . '" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_color_picker($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cplink_option_name($field) . '" value="' . $value . '" ';
        $html .= $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_input i_color_picker"  >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }


    public static function create_section_for_date_picker($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cplink_option_name($field) . '" value="' . $value . '" ';
        $html .= $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_input i_datepicker"  >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_intro_view($field, $value = '')
    {
        //if( !$value )
        $value = CPLINK::isset_return($field, 'value');
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" value="' . htmlspecialchars($value) . '" ' . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_input i_click_checkall" readonly  >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_html_view($field, $value = '')
    {
        //if( !$value )
        $value = CPLINK::isset_return($field, 'value');
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        if ($field['subtitle'])
            $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        if ($field['description'])
            $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    /*
     * export orders request function
     */
    public static function export_orders_to_sage_request()
    {
        if (!(isset($_REQUEST['action']) && 'i_export_orders_to_sage_request' == $_POST['action']))
            return;
        if (!empty($_POST['order_ids'])) {

            CPLINK::max_server_ini();
            $return = self::export_orders_to_sage($_POST['order_ids']);

        } else {
            $return = array(
                'success' => false,
                'html' => '<h3>' . __('Please Select Order', CPLINK_NAME) . '</h3>'
            );
        }


        echo json_encode($return);
        exit;
    }

    public static function export_orders_to_sage($orders = [])
    {
        $request_data_result = [];
        $error_results = array();

        if (!empty($orders)) {
            //here we need customer_number and ar_division_number from options
            global $CP_Sage;
            global $wpdb;
            global $cp_scope_cf, $cp_modules_cf, $cp_shipping_methods;
            $cplink_modules_options = $cp_modules_cf;
            $cplink_options = $cp_scope_cf;
            $table_prefix = CPLINK_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';
            $global_data_to_send = [];

            foreach ($orders as $order) {
                $order = intval($order);
                $orderq = wc_get_order($order);
                $db_result = $wpdb->get_results("SELECT * FROM $table_name WHERE web_sales_order_no = $order");
                $db_result = $db_result[0];
                if (empty($orderq)) {
                    $export_count = 1;
                    if (!is_null($db_result->export_count)) {
                        $export_count = $db_result->export_count + 1;
                    }
                    $updating_info = array('status' => '2', 'update_time' => wp_date("Y-m-d h:i:s"));
                    $msg = __('The Order is missing from WooCommerce orders list', CPLINK_NAME);
                    $updating_info['message'] = $msg;
                    $updating_info['export_count'] = $export_count;
                    $update_result = $wpdb->update($table_name, $updating_info, array('web_sales_order_no' => $order));
                    $error_results[ strval($order) ] = $msg;
                    CPLINK::sendApiErrorMessageToClient('Order - '.$order . ' - '.$msg, '', 'orderExport');
                    continue;
                }
                $order_date = $db_result->created_time;

                $ar_division_number = '';
                $customer_number = '';

                $order_data = new WC_Order($order);

                $billing_address = $order_data->get_address();
                $shipping_address = $order_data->get_address('shipping');
                /*i_print($billing_address);
                i_print($shipping_address);
                exit;*/
                if (empty($shipping_address['address_1'])) {
                    $shipping_address = $billing_address;
                }

                $shipping_total = $order_data->get_shipping_total();

                foreach( $order_data->get_items( 'shipping' ) as $item_id => $item ){
                    // Get the data in an unprotected array
                    $item_data = $item->get_data();

                    $shipping_data_id           = $item_data['id'];
                    $shipping_data_order_id     = $item_data['order_id'];
                    $shipping_data_name         = $item_data['name'];
                    $shipping_data_method_title = $item_data['method_title'];
                    $shipping_data_method_id    = $item_data['method_id'];
                    $shipping_data_instance_id  = $item_data['instance_id'];
                    $shipping_data_total        = $item_data['total'];
                    $shipping_data_total_tax    = $item_data['total_tax'];
                    $shipping_data_taxes        = $item_data['taxes'];
                }


                $key_to_find = $shipping_data_method_id;
                if($shipping_data_method_id == 'usps_simple'){
                    $key_to_find = 'usps_simple_'.strtolower(str_replace(' ','_',$shipping_data_name));
                }

                $shipping_method = '';
                if(isset($cp_shipping_methods[$key_to_find])){
                    $shipping_method = $cp_shipping_methods[$key_to_find];
                }

                $items = $order_data->get_items();
                $products_list = array();
                $count = 0;
                foreach ($items as $item) {
                    $product = $item->get_product();
                    if( $product ) {
                        $productID = $item->get_product_id();
                        //i_print($product->get_price());
                        $products_list[$count]['quantity'] = $item->get_quantity();
                        if (!empty($product->get_sku())) {
                            $products_list[$count]['item_code'] = $product->get_sku();
                        } else {
                            //_cp_item_code
                            $cp_item_code = CPLINK::product_item_code($productID);
                            $products_list[$count]['item_code'] = $cp_item_code;
                        }
                        $products_list[$count]['price'] = CPLINK::woo_product_price($product->get_price(), $product, $item->get_quantity());
                        $count++;
                    }
                }
                if( !count($products_list) ){
                    $export_count = 1;
                    if (!is_null($db_result->export_count))
                        $export_count = $db_result->export_count + 1;

                    $product_name = $item->get_name();

                    $updating_info = array('status' => '2', 'update_time' => wp_date("Y-m-d h:i:s"));
                    $msg = __('The Order Product ('.$product_name.') is missing from WooCommerce Products list', CPLINK_NAME);
                    CPLINK::sendApiErrorMessageToClient('Order - '.$order . ' - '.$msg, '', 'orderExport');
                    $updating_info['message'] = $msg;
                    $updating_info['export_count'] = $export_count;
                    $update_result = $wpdb->update($table_name, $updating_info, array('web_sales_order_no' => $order));
                    $error_results[ strval($order) ] = $msg;
                    continue;
                }

                $comment_item_code = CPLINK::isset_return($cp_modules_cf, 'comment_item_code');
                $customer_note = $order_data->get_customer_note();
                if(!empty($comment_item_code) && !empty($customer_note)){
                    $products_list[$count]['price'] = 0;
                    $products_list[$count]['quantity'] = 0;
                    $products_list[$count]['item_code'] = $comment_item_code;
                    $products_list[$count]['comment'] = $customer_note;
                }

                $settings_name = "cplink-settings-modules";
                $settings_name_general = "cplink-settings";

                $userID = $order_data->get_user_id();


                $request_Customer_data = false;
                $for_current_user = false;
                $for_guest_user = false;

                $order_ar_division_number = get_post_meta($order, 'cp_ar_division_no', true);
                $order_customer_number = get_post_meta($order, 'cp_customer_no', true);

                $ar_division_number = '';
                $customer_number = '';

                if (trim($order_ar_division_number) == '' || trim($order_customer_number) == '') {
                    if ($userID) {
                        $temp_ar_division_number = get_user_meta($userID, 'cp_ar_division_no', true);
                        $temp_customer_number = get_user_meta($userID, 'cp_customer_no', true);
                        if (trim($temp_ar_division_number) == '' || trim($temp_customer_number) == '') {
                            if ($cplink_modules_options['use_default_customer']) {
                                $temp_ar_division_number = $cplink_modules_options['ar_division_no'];
                                $temp_customer_number = $cplink_modules_options['customer_no'];
                                if (trim($temp_ar_division_number) == '' || trim($temp_customer_number) == '') {
                                    $request_Customer_data = true;
                                    $for_current_user = true;
                                    $for_guest_user = false;
                                } else {
                                    $ar_division_number = $temp_ar_division_number;
                                    $customer_number = $temp_customer_number;
                                }
                            } else {
                                $request_Customer_data = true;
                                $for_current_user = true;
                                $for_guest_user = false;
                            }

                            //$selected_user = get_user_meta( $userID );
                            $first_name = get_user_meta($userID, 'first_name', true);
                            $last_name = get_user_meta($userID, 'last_name', true);
                            $billing_address_1 = substr(get_user_meta($userID, 'billing_address_1', true),0,30);
                            $billing_address_2 = substr(get_user_meta($userID, 'billing_address_2', true),0,30);
                            $billing_email = get_user_meta($userID, 'billing_email', true);
                            $billing_phone = get_user_meta($userID, 'billing_phone', true);
                            $billing_country = get_user_meta($userID, 'billing_country', true);

                            $billing_city = substr(get_user_meta($userID, 'billing_city', true),0,20);
                            $billing_state = substr(get_user_meta($userID, 'billing_state', true),0,2);
                            $billing_zip = substr(get_user_meta($userID, 'billing_postcode', true),0,10);

                        } else {
                            $ar_division_number = $temp_ar_division_number;
                            $customer_number = $temp_customer_number;
                        }
                    } else {
                        $temp_ar_division_number = get_post_meta($order, 'cp_ar_division_no', true);
                        $temp_customer_number = get_post_meta($order, 'cp_customer_no', true);


                        if (trim($temp_ar_division_number) == '' || trim($temp_customer_number) == '') {
                            $first_name = $billing_address['first_name'];
                            $last_name = $billing_address['last_name'];

                            $billing_address_1 = substr($billing_address['address_1'],0,30);
                            $billing_address_2 = substr($billing_address['address_2'],0,30);
                            $billing_email = $billing_address['email'];
                            $billing_phone = $billing_address['phone'];
                            $billing_country = $billing_address['country'];
                            $billing_city = substr($billing_address['city'],0,20);
                            $billing_state = substr($billing_address['state'],0,2);
                            $billing_zip = substr($billing_address['postcode'],0,10);

                            $request_Customer_data = true;
                            $for_current_user = false;
                            $for_guest_user = true;
                        } else {
                            $ar_division_number = $temp_ar_division_number;
                            $customer_number = $temp_customer_number;
                        }
                    }

                    if ($request_Customer_data) {

                        $data = array(
                            'customer_name' => substr($first_name . ' ' . $last_name,0,30),
                            'address1' => $billing_address_1,
                            'address2' => $billing_address_2,
                            'city' => $billing_city,
                            'state' => $billing_state,
                            'zip' => $billing_zip,
                            'country' => CPLINK::get_country_iso3($billing_country),
                            'email' => $billing_email,
                            'phone' => $billing_phone,
                            /*'price_level' => '',*/
                            'tax_schedule' => CPLINK::isset_return($cp_scope_cf, 'tax_schedule'),
                            'terms_code' => CPLINK::isset_return($cp_scope_cf, 'terms_code'),
                            /*'comments' => '',*/
                        );
                        if (trim($cplink_options['division_no']) != '') {
                            $data['ar_division_number'] = $cplink_options['division_no'];
                        }
                        $create_sage_customer_for_existing_user = CPLINK::isset_return($cp_modules_cf, 'create_sage_customer_for_existing_user');
                        $create_sage_customer_for_guest_user = CPLINK::isset_return($cp_modules_cf, 'create_sage_customer_for_guest_user');

                        if(($for_current_user && $create_sage_customer_for_existing_user) || ($for_guest_user && $create_sage_customer_for_guest_user)){

                            $createCustomer = $CP_Sage->createCustomers($data);

                            if ($createCustomer[0]->success) {
                                $ar_division_number = $createCustomer[0]->data->ar_division_number;
                                $customer_number = $createCustomer[0]->data->customer_number;
                                if ($for_current_user) {
                                    update_user_meta($userID, 'cp_ar_division_no', $ar_division_number);
                                    update_user_meta($userID, 'cp_customer_no', $customer_number);
                                }

                                $create_sage_customer_addresses = CPLINK::isset_return($cp_modules_cf, 'create_sage_customer_addresses');
                                if($create_sage_customer_addresses){
                                    $data = array(
                                        'shipto_code' => '01',
                                        'customer_number' => $customer_number,
                                        'ar_division_number' => $ar_division_number,
                                        'name' => substr($first_name . ' ' . $last_name,0,30),
                                        'address1' => substr($shipping_address['address_1'],0,30),
                                        'address2' => substr($shipping_address['address_2'],0,30),
                                        'city' => substr($shipping_address['city'],0,20),
                                        'state' => substr($shipping_address['state'],0,2),
                                        'zip' => substr($shipping_address['postcode'],0,10),
                                        'country' => CPLINK::get_country_iso3($shipping_address['country']),
                                        'email' => $shipping_address['email'],
                                        'phone' => $shipping_address['phone'],
                                        'tax_schedule' => CPLINK::isset_return($cp_scope_cf, 'tax_schedule'),
                                    );
                                    $createCustomerAddress = $CP_Sage->createCustomersAddresses($data);
                                    if ($createCustomerAddress[0]->success) {
                                        $shipto_code = $createCustomerAddress[0]->data->shipto_code;
                                        update_post_meta($order, '_shipping_cp_shipto_code',$shipto_code );

                                    }
                                }
                            }else{
                                $export_count = 1;
                                if (!is_null($db_result->export_count))
                                    $export_count = $db_result->export_count + 1;


                                $updating_info = array('status' => '2', 'update_time' => wp_date("Y-m-d h:i:s"));
                                $msg = $createCustomer[0]->message;
                                CPLINK::sendApiErrorMessageToClient('Order - '.$order . ' - '.$msg, '', 'orderExport');
                                $updating_info['message'] = $msg;
                                $updating_info['export_count'] = $export_count;
                                $update_result = $wpdb->update($table_name, $updating_info, array('web_sales_order_no' => $order));
                                $error_results[ strval($order) ] = $msg;
                                continue;
                            }
                        }
                    }
                } else {
                    $ar_division_number = $order_ar_division_number;
                    $customer_number = $order_customer_number;
                }
                if (trim($ar_division_number) != '' && trim($customer_number) != '') {

                    update_post_meta($order, 'cp_ar_division_no', $ar_division_number);
                    update_post_meta($order, 'cp_customer_no', $customer_number);
                }else{
                    $export_count = 1;
                    if (!is_null($db_result->export_count))
                        $export_count = $db_result->export_count + 1;


                    $updating_info = array('status' => '2', 'update_time' => wp_date("Y-m-d h:i:s"));
                    $msg = __('There is no Customer Number/Division Number.', CPLINK_NAME);;
                    CPLINK::sendApiErrorMessageToClient('Order - '.$order . ' - '.$msg, '', 'orderExport');
                    $updating_info['message'] = $msg;
                    $updating_info['export_count'] = $export_count;
                    $update_result = $wpdb->update($table_name, $updating_info, array('web_sales_order_no' => $order));
                    $error_results[ strval($order) ] = $msg;
                    continue;
                }

                $data_to_send = array(
                    'ar_division_number' => $ar_division_number,
                    'customer_number' => $customer_number,
                    'order_date' => $order_date,
                    'billto_name' => substr($billing_address['first_name'] . ' ' . $billing_address['last_name'],0,30),
                    'billto_address1' => substr($billing_address['address_1'],0,30),
                    'billto_address2' => substr($billing_address['address_2'],0,30),
                    'billto_city' => substr($billing_address['city'],0,20),
                    'billto_state' => substr($billing_address['state'],0,2),
                    'billto_zip' => substr($billing_address['postcode'],0,10),
                    'billto_country' => CPLINK::get_country_iso3($billing_address['country']),
                    'shipto_name' => substr($shipping_address['first_name'] . ' ' . $shipping_address['last_name'],0,30),
                    'shipto_address1' => substr($shipping_address['address_1'],0,30),
                    'shipto_address2' => substr($shipping_address['address_2'],0,30),
                    'shipto_city' => substr($shipping_address['city'],0,20),
                    'shipto_state' => substr($shipping_address['state'],0,2),
                    'shipto_zip' => substr($shipping_address['postcode'],0,10),
                    'shipto_country' => CPLINK::get_country_iso3($shipping_address['country']),
                    'confirm_to' => substr($billing_address['first_name'] . ' ' . $billing_address['last_name'],0,30),
                    'freight_amount' => $shipping_total,
                    'external_order_number' => $order,
                    'items' => $products_list,
                );

                $sage_order_number = CPLINK::isset_return($cp_modules_cf, 'sage_order_number');
                $order_number_prefix = CPLINK::isset_return($cp_modules_cf, 'order_number_prefix');
                if($sage_order_number == '0' && trim($order_number_prefix) != ''){
                    if (strlen($order_number_prefix) > 3) {
                        $order_number_prefix = substr($order_number_prefix, 0, 3);
                    }
                    $data_to_send['order_number_prefix'] = $order_number_prefix;
                }
                if($sage_order_number == '1'){
                    $cp_sales_order_number = get_post_meta($order, 'cp_sales_order_number', true);
                    if(trim($cp_sales_order_number) != ''){
                        $data_to_send['sales_order_number'] = $cp_sales_order_number;
                    }
                }

                $order_status = CPLINK::isset_return($cp_modules_cf, 'default_order_status');
                if($order_status != ''){
                    $data_to_send['order_status'] = $order_status;
                }

                $tax_schedule = self::getTaxSchedule($ar_division_number,$customer_number);
                if($tax_schedule != ''){
                    $data_to_send['tax_schedule'] = $tax_schedule;
                }

                if(!empty($shipping_method) && trim($shipping_method) != ''){
                    $data_to_send['shipping_code'] = $shipping_method;
                }
                $shipping_cp_shipto_code = get_post_meta($order, '_shipping_cp_shipto_code', true);
                if(!empty($shipping_cp_shipto_code)){
                    $data_to_send['shipto_code'] = $shipping_cp_shipto_code;
                }

                $cp_po_number = get_post_meta($order, 'cp_po_number', true);
                if(!empty($cp_po_number)){
                    $data_to_send['po_number'] = $cp_po_number;
                }
                $cp_comment = get_post_meta($order, 'cp_comment', true);
                if(!empty($cp_comment)){
                    $data_to_send['comments'] = $cp_comment;
                }

                if($userID){
                    $web_user_id = get_user_meta($order, 'cp_web_user_id', true);
                    if(!empty($web_user_id)){
                        $data_to_send['user_id'] = $web_user_id;
                    }
                }

                $payment_method = $order_data->get_payment_method();
                $check_number = $order_data->get_transaction_id();
                $is_reference = true;
                $payment_type = '';
                if ($payment_method == 'sagepaymentsusaapi') {
                    $_sageresult = get_post_meta($order, '_sageresult', true);
                    $card_type = self::getPaymentMethodCode($_sageresult['Card Type']);
                    $payment_type = '';
                    if(!empty($card_type)){
                        if(isset($cp_scope_cf['payment_'.strtolower($card_type)])){
                            $payment_type = $cp_scope_cf['payment_'.strtolower($card_type)];
                        }
                    }
                    $is_reference = false;
                }elseif($payment_method == 'zeamster'){
                    $zp_payment_data = get_post_meta( $order, '_zp_payment_data', true );
                    if(isset($zp_payment_data['card_type'])){
                        $card_type = self::getPaymentMethodCode($zp_payment_data['card_type']);
                    }else{
                        $card_type = self::getPaymentMethodCode($zp_payment_data['account_type']);
                    }
                    $payment_type = '';
                    if(!empty($card_type)){
                        if(isset($cp_scope_cf['payment_'.strtolower($card_type)])){
                            $payment_type = $cp_scope_cf['payment_'.strtolower($card_type)];
                        }
                    }
                    $is_reference = false;
                }elseif($payment_method == 'ppcp-gateway'){
                    $payment_type = 'OTHER';
                }

                /*$directtokens = get_post_meta( $order, '_zp_payment_data', true );*/
                /*$tokens = $orderq->get_payment_tokens();
                i_print($tokens);
                exit;*/

                if(!empty($payment_type) && !empty($check_number)){
                    $data_to_send['payment_type'] = $payment_type;

                    if($is_reference ){
                        $data_to_send['reference_number'] = $check_number;
                    }
                    else {
                        //$data_to_send['check_number'] = $check_number;
                        if($payment_method == 'sagepaymentsusaapi'){
                            $_sageresult = get_post_meta($order, '_sageresult', true);
                            //i_print($_sageresult);
                            if (!empty($_sageresult)) {
                                $last_4_dig_string = $_sageresult['Card Number'];
                                $last_4_dig = '';
                                if ($last_4_dig_string) {
                                    $last_4_dig_string = explode("-", $last_4_dig_string);
                                    $last_4_dig = end($last_4_dig_string);
                                }
                                $card_types_array = array(
                                    'amex' => '3',
                                    'visa' => '4',
                                    'mastercard' => '5',
                                    'discover' => '6',
                                    'jcb' => '7',
                                );
                                $cardType = '';
                                if (isset($card_types_array[strtolower($_sageresult['Card Type'])])) {
                                    $cardType = $card_types_array[strtolower($_sageresult['Card Type'])];
                                }
                                $expiration_date_year = '';
                                $expiration_date_month = '';
                                if (isset($_sageresult['Expiry Date'])) {
                                    $expDate = $_sageresult['Expiry Date'];
                                    $expiration_date_month = substr($expDate, 0, 2);
                                    if (strlen($expDate) > 4) {
                                        $expiration_date_year = substr($expDate, -4);
                                    } else {
                                        $expiration_date_year = '20' . substr($expDate, -2);
                                    }
                                }
                                $currToken = '';
                                $currTokenTemp = get_post_meta($order, '_SageToken', true);

                                if (isset($currTokenTemp)) {
                                    $currToken = $currTokenTemp;
                                }

                                $data_to_send['cc_guid'] = $currToken;
                                $data_to_send['card_type'] = $cardType; // American Express = 3, Visa = 4, MasterCard = 5, Discover = 6, JCB = 7
                                $data_to_send['card_holder_name'] = substr($billing_address['first_name'] . ' ' . $billing_address['last_name'], 0, 30);
                                $data_to_send['last_4_cc_numbers'] = $last_4_dig;
                                $data_to_send['expiration_date_year'] = $expiration_date_year;
                                $data_to_send['expiration_date_month'] = $expiration_date_month;
                                $data_to_send['cc_transaction_id'] = $check_number;
                                $data_to_send['cc_authorization_number'] = $_sageresult['code'];
                                $data_to_send['payment_type_category'] = "P";
                                if (isset($_sageresult['timestamp'])) {
                                    $data_to_send['authorization_date'] = date_format(date_create($_sageresult['timestamp']), 'Ymd');
                                    $data_to_send['authorization_time'] = date_format(date_create($_sageresult['timestamp']), 'His');
                                }
                                $data_to_send['transaction_amount'] = $orderq->get_total();

                                $data_to_send['avs_address1'] = substr($billing_address['address_1'], 0, 30);
                                $data_to_send['avs_address2'] = substr($billing_address['address_2'], 0, 30);
                                $data_to_send['avs_zip'] = substr($billing_address['postcode'], 0, 10);
                                $data_to_send['avs_city'] = substr($billing_address['city'], 0, 20);
                                $data_to_send['avs_state'] = substr($billing_address['state'], 0, 2);
                                $data_to_send['avs_country'] = CPLINK::get_country_iso3($billing_address['country']);
                                /*i_print($data_to_send);
                                exit;*/
                            }
                        }elseif($payment_method == 'zeamster'){
                            if(!empty($zp_payment_data)){
                                $token_table_name = $wpdb->prefix . 'woocommerce_payment_tokens';
                                $zp_payment_data_local = get_post_meta( $order, '_zp_payment_data_local', true );
                                if(isset($zp_payment_data['saved_account_vault_id'])){
                                    $token = $zp_payment_data['saved_account_vault_id'];
                                }else{
                                    $token = $zp_payment_data['account_vault_id'];
                                }
                                $db_result = $wpdb->get_row("SELECT * FROM $token_table_name WHERE token = '$token'",ARRAY_A);
                                $token_metadata = WC_Payment_Token_Data_Store::get_metadata( $db_result['token_id'] );
                                /*i_print($payment_type);
                                i_print($token_metadata);
                                i_print('==========');
                                i_print("SELECT * FROM $token_table_name WHERE token = $token");
                                i_print($db_result);
                                i_print($zp_payment_data);
                                i_print($token_metadata);
                                i_print($zp_payment_data_local);
                                exit;*/


                                $last_4_dig = $zp_payment_data['last_four'];

                                $card_types_array = array(
                                    'amex' => '3',
                                    'visa' => '4',
                                    'mastercard' => '5',
                                    'discover' => '6',
                                    'jcb' => '7',
                                );
                                $cardType = '';
                                if(isset($zp_payment_data['card_type'])){
                                    $card_type = $zp_payment_data['card_type'];
                                }else{
                                    $card_type = $zp_payment_data['account_type'];
                                }

                                if (isset($card_types_array[strtolower($card_type)])) {
                                    $cardType = $card_types_array[strtolower($card_type)];
                                }

                                if(!empty($token_metadata)){
                                    $expiration_date_year = $token_metadata['expiry_year'][0];
                                    $expiration_date_month = $token_metadata['expiry_month'][0];
                                }elseif (!empty($zp_payment_data_local)){
                                    $expiration_date_year = $zp_payment_data_local['expiry_year'];
                                    $expiration_date_month = $zp_payment_data_local['expiry_month'];
                                }

                                $currToken = $token;

                                $data_to_send['cc_guid'] = $currToken;
                                $data_to_send['card_type'] = $cardType; // American Express = 3, Visa = 4, MasterCard = 5, Discover = 6, JCB = 7
                                $data_to_send['card_holder_name'] = substr($billing_address['first_name'] . ' ' . $billing_address['last_name'], 0, 30);
                                $data_to_send['last_4_cc_numbers'] = $last_4_dig;
                                $data_to_send['expiration_date_year'] = $expiration_date_year;
                                $data_to_send['expiration_date_month'] = $expiration_date_month;
                                $data_to_send['cc_transaction_id'] = $check_number;
                                $data_to_send['cc_authorization_number'] = $zp_payment_data['auth_code'];
                                $data_to_send['payment_type_category'] = "P";
                                if (isset($zp_payment_data['transaction_date'])) {
                                    $data_to_send['authorization_date'] = date_format(date_create($zp_payment_data['transaction_date']), 'Ymd');
                                    $data_to_send['authorization_time'] = date_format(date_create($zp_payment_data['transaction_date']), 'His');
                                }
                                $data_to_send['transaction_amount'] = $orderq->get_total();

                                $data_to_send['avs_address1'] = substr($billing_address['address_1'], 0, 30);
                                $data_to_send['avs_address2'] = substr($billing_address['address_2'], 0, 30);
                                $data_to_send['avs_zip'] = substr($billing_address['postcode'], 0, 10);
                                $data_to_send['avs_city'] = substr($billing_address['city'], 0, 20);
                                $data_to_send['avs_state'] = substr($billing_address['state'], 0, 2);
                                $data_to_send['avs_country'] = CPLINK::get_country_iso3($billing_address['country']);


                            }
                        }

                    }
                }




                $global_data_to_send[] = $data_to_send;

            }
            /*i_print($payment_method);
            i_print($payment_type);
            i_print($check_number);*/
            //i_print($global_data_to_send);exit;
            /*return;*/
            if (!empty($global_data_to_send)) {
                $api_response = $CP_Sage->createSalesorders($global_data_to_send);
                //if()
                /*i_print($global_data_to_send);
                i_print($api_response,true);*/
                if ($api_response->success) {
                    $request_data_result['success'] = true;
                    $count = 0;
                    $html = '';
                    foreach ($api_response->data as $item) {
                        $order_id = intval($item->data->external_order_number);
                        $db_result = $wpdb->get_results("SELECT * FROM $table_name WHERE web_sales_order_no = $order_id");
                        $db_result = $db_result[0];
                        $export_count = 1;
                        if (!is_null($db_result->export_count)) {
                            $export_count = $db_result->export_count + 1;
                        }
                        $updating_info = array('status' => '2', 'update_time' => wp_date("Y-m-d h:i:s"));
                        $updating_info['message'] = $item->message;
                        $updating_info['export_count'] = $export_count;
                        if ($item->success) {
                            $updating_info['status'] = '1';
                            /*$request_data_result['data'][$count]['message'] = $api_response[0]->message;
                            $request_data_result['data'][$count]['message'] = $api_response[0]->message;*/
                            update_post_meta($order_id, 'cp_sales_order_number', sanitize_text_field($item->data->sales_order_number));
                        }
                        /*else{
                            $updating_info['status'] = '2';
                        }*/
                        $update_resutl = $wpdb->update($table_name, $updating_info, array('web_sales_order_no' => $order_id));
                        $count++;
                        $html .= '<div class="order_export_status">';
                        $message_from_sage = $item->message;
                        $message = str_replace("Lines:", "", $message_from_sage);
                        $message = str_replace("Header:", "", $message);
                        if ($item->success && $item->message == '') {
                            $message = '<div class="cplink_resp_">'.$order_id . ' - Successfully exported</div>';
                        } else {
                            $message = '<div class="cplink_resp_error">'.$order_id . ' - '.$message.'</div>';
                            $data_to_attach = [];
                            foreach ($global_data_to_send as $item) {
                                if ($item['external_order_number'] == $order_id) {
                                    $data_to_attach = $item;
                                }
                            }
                            CPLINK::sendApiErrorMessageToClient('Order - '.$order_id . ' - '.$message_from_sage, $data_to_attach, 'orderExport');
                        }
                        $html .= $message;
                        $html .= '</div>';
                    }
                    $request_data_result['html'] = $html;
                } else {
                    //i_print($api_response);
                    $request_data_result['success'] = false;
                    $request_data_result['html'] = $api_response->message;
                    /*$request_data_result['html'] = __('Please check the API Base URL',CPLINK_NAME);*/
                    if(is_null($api_response)){
                        $request_data_result['success'] = false;
                        $request_data_result['html'] = __('There is no connection with api server',CPLINK_NAME);
                        CPLINK::sendApiErrorMessageToClient('There is no connection with api server', $global_data_to_send, 'orderExport');
                    }
                }
            } else {
                $request_data_result['html'] = '';
                $request_data_result['success'] = false;
                CPLINK::sendApiErrorMessageToClient('There is no data to send', '', 'orderExport');
            }
            $request_data_result['error_results'] = $error_results;
        } else {
            $request_data_result['html'] = __('There is no chosen orders',CPLINK_NAME);
            $request_data_result['success'] = false;
            //CPLINK::sendApiErrorMessageToClient('There is no chosen orders', '', 'orderExport');
        }

        return $request_data_result;

    }

    public static function getTaxSchedule($arDivisionNo, $customerNo, $order = null){
        //$arDivisionNo = '01';$customerNo = 'ABF';
        global $cp_scope_cf,$wpdb;
        $taxSchedule = CPLINK::isset_return($cp_scope_cf, 'tax_schedule');
        $sageCustomer = CPLINK::getCustomer($customerNo, $arDivisionNo, 'primary_shipto_code,tax_schedule');
        if($sageCustomer['tax_schedule'] != ''){
            $taxSchedule = $sageCustomer['tax_schedule'];
        }else{
            if($sageCustomer['primary_shipto_code'] != ''){
                $table_prefix = CPLINK_DB_PREFIX;

                $table_name = $table_prefix . 'customers_shipto';
                $shipto_code = $sageCustomer['primary_shipto_code'];

                $db_result = $wpdb->get_row("SELECT * FROM $table_name WHERE  LOWER(`customer_no`) = LOWER('$customerNo') AND `ar_division_no` = '$arDivisionNo' AND `shipto_code` = '$shipto_code'",ARRAY_A);

                if(!empty($db_result)){
                    if($db_result['tax_schedule'] != ''){
                        $taxSchedule = $db_result['tax_schedule'];
                    }
                }
            }
        }
        return $taxSchedule;
    }

    public static function getPaymentMethodCode($ccType)
    {
        $cPmntMethCode = '';
        if (!empty($ccType)) {
            $ccType = strtolower($ccType);

            $visaTypes = ['v', 'vi', 'vis', 'visa'];
            $mCardTypes = ['m', 'mc', 'mcard', 'mascard', 'mastercard'];
            $amExTypes = ['a', 'ae', 'amex', 'americanexpress', 'american express'];
            $discoverTypes = ['d', 'di', 'ds', 'dis', 'discover'];
            $JCBTypes = ['j', 'jcb'];

            if (in_array($ccType, $visaTypes)) {
                $cPmntMethCode = 'VI';
            } else if (in_array($ccType, $mCardTypes)) {
                $cPmntMethCode = 'MC';
            } else if (in_array($ccType, $amExTypes)) {
                $cPmntMethCode = 'AE';
            } else if (in_array($ccType, $discoverTypes)) {
                $cPmntMethCode = 'DI';
            } else if (in_array($ccType, $JCBTypes)) {
                $cPmntMethCode = 'JCB';
            }
        }
        return $cPmntMethCode;
    }

    public static function delete_queue_orders()
    {
        if (!empty($_POST['order_ids'])) {
            $orders = $_POST['order_ids'];
            global $wpdb;
            $table_prefix = CPLINK_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';
            foreach ($orders as $order) {
                $ql_result = $wpdb->query("UPDATE $table_name SET active = 0 where web_sales_order_no = $order");
            }
            $return['success'] = true;
        } else {
            $return = array(
                'success' => false,
                'html' => '<h3>' . __('Please Select Order', CPLINK_NAME) . '</h3>'
            );
        }


        echo json_encode($return);
        exit;
    }

    public static function import_order_to_queue()
    {
        if (!empty($_POST['order_id'])) {
            $order_id = intval($_POST['order_id']);
            $order = new WC_Order($order_id);
            $order_date = $order->order_date;

            global $wpdb;
            $table_prefix = CPLINK_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';

            /*$data = array(
                'web_sales_order_no' => $order_id,
                'message' => '',
                'export_count' => 0,
                'created_time' => wp_date("Y-m-d h:i:s",strtotime($order_date)),
            );

            $result = $wpdb->insert( $table_name, $data);*/
            $result = $wpdb->query("UPDATE $table_name SET active = 1 where web_sales_order_no = $order_id");
            if ($result) {
                $return = array(
                    'success' => true,
                    'html' => '<span>' . __('Successfully imported!', CPLINK_NAME) . '</span>'
                );
            } else {
                $return = array(
                    'success' => false,
                    'html' => '<span>' . __('Something went wrong!', CPLINK_NAME) . '</span>'
                );
            }
        } else {
            $return = array(
                'success' => false,
                'html' => '<span>' . __('Something went wrong!', CPLINK_NAME) . '</span>'
            );
        }

        echo json_encode($return);
        exit;
    }

    /*
     * AJAX
     */
    public static function cplink_import()
    {
        if (!(isset($_REQUEST['action']) && 'cplink_import' == $_POST['action']))
            return;

        CPLINK::max_server_ini();

        $run_module_src = $_POST['cplink_import_source'];
        $run_module_type = $_POST['cplink_import_type'];

        if( $run_module_type == 'invoices' ) {
            $additional_import = $_POST;
            $additional_import['cplink_import_type'] = 'invoices_history';
            $return = CPLINK::cplink_import($additional_import);
        } elseif( $run_module_type == 'salesorders' ) {
            $additional_import = $_POST;
            $additional_import['cplink_import_type'] = 'salesorders_history';
            $return = CPLINK::cplink_import($additional_import);
        }
        if( $run_module_src == 'purge' ){
            $import_info = array(
                'cplink_import_type' => $run_module_type,
                'cplink_import_source' => 'sync'
            );
            $import_sync_result = CPLINK::cplink_import($import_info);
        }

        $return = CPLINK::cplink_import($_POST);

        echo json_encode($return);
        exit;
    }

    public static function ajax_response_example()
    {

        if (!(isset($_REQUEST['action']) && 'i_new_distributor' == $_POST['action']))
            return;

        $return = array(
            'status' => false,
            'html' => '<h3> There is ERROR!!! </h3>'
        );


        echo json_encode($return);
        exit;
    }

}