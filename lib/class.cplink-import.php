<?php

    if( !current_user_can('administrator') )
        return 'You have not permission';
    if( !empty($sage_products) ) {
        foreach ($sage_products as $sage_product) {
            $sage_product = (array)$sage_product;
            $post_type = 'product';
            //i_print($sage_product);
            $item_code = $this->isset_return($sage_product, 'item_code');
            $woo_sage_map = array(
                '_price' => 'standard_unit_price',// ? need to compare with regular and sale price to decide
                '_regular_price' => 'standard_unit_price',
                '_weight' => 'weight',
                '_cp_item_code' => 'item_code',
                '_cp_tax_class' => 'tax_class',
                '_cp_warranty_code' => 'warranty_code',
                '_cp_commission_rate' => 'commission_rate',
                '_cp_sale_method' => 'sale_method',
            );

            //Get Categpries
            $product_categories = array();
            $cat_i = 1;
            while( isset($sage_product['category'.$cat_i]) ) {
                array_push($product_categories, $sage_product['category'.$cat_i] );
                $cat_i++;
            }

            if( $item_code ) {
                $product_data = array(
                    'post_title' => $item_code,
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_type' => $post_type,
                );
                i_print($product_data);

                    $args = array(
                        'meta_query' => array(
                            array(
                                'key' => '_cp_item_code',
                                'value' => $item_code
                            )
                        ),
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                    );
                    $posts_exist = get_posts($args); //Check if product exist by unical key
                    if( empty( $posts_exist) ){
                        i_print('Product with $item_code='.$item_code.' not exist');

                        if( $item_code == '/100-AA' ) {}
                        $post_id = wp_insert_post($product_data);
                    } else {
                        $post_id = $exist_post_id = $posts_exist[0];
                        //i_print('Product with $item_code='.$item_code.' already exist');
                    }

                    if( $post_id ) {
                        foreach ($woo_sage_map as $woo_field => $sage_field) {
                            $field_value = $this->isset_return($sage_product, $sage_field);
                            if ($field_value) {
                                //i_print($woo_field.' = '.$field_value);
                                update_post_meta($post_id, $woo_field, $field_value);
                            }
                        }
                    }
            }
        }
    }

    /**/
    /*$post_id = wp_insert_post($product_data);
    wp_set_object_terms($post_id, 'simple', 'product_type');
    //wp_set_object_terms( $post_id, 'variable', 'product_type' );
    update_post_meta($post_id, '_price', '156');
    update_post_meta($post_id, '_featured', 'yes');
    update_post_meta($post_id, '_stock', '23');
    update_post_meta($post_id, '_stock_status', 'instock');
    update_post_meta($post_id, '_sku', 'jk01');

    //update_post_meta( $post_id, '_thumbnail_id', '13' );
    */

    /*update_post_meta( $post_id, '_visibility', 'visible' );
    update_post_meta( $post_id, '_stock_status', 'instock');
    update_post_meta( $post_id, '_thumbnail_id', '13' );
    update_post_meta( $post_id, 'total_sales', '0' );
    update_post_meta( $post_id, '_downloadable', 'no' );
    update_post_meta( $post_id, '_virtual', 'yes' );
    update_post_meta( $post_id, '_regular_price', '' );
    update_post_meta( $post_id, '_sale_price', '' );
    update_post_meta( $post_id, '_purchase_note', '' );
    update_post_meta( $post_id, '_featured', 'no' );
    update_post_meta( $post_id, '_weight', '' );
    update_post_meta( $post_id, '_length', '' );
    update_post_meta( $post_id, '_width', '' );
    update_post_meta( $post_id, '_height', '' );
    update_post_meta( $post_id, '_sku', '' );
    update_post_meta( $post_id, '_product_attributes', array() );
    update_post_meta( $post_id, '_sale_price_dates_from', '' );
    update_post_meta( $post_id, '_sale_price_dates_to', '' );
    update_post_meta( $post_id, '_price', '' );
    update_post_meta( $post_id, '_sold_individually', '' );
    update_post_meta( $post_id, '_manage_stock', 'no' );
    update_post_meta( $post_id, '_backorders', 'no' );*/

/*class CPLINK_IMPORT
{

    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    private static function init_hooks()
    {
        //if (!self::$initiated)
            //self::run_import_action();

        self::$initiated = true;
    }


    public static function import_action()
    {
        if( is_user_logged_in() && is_admin() ) {
            $post_type = 'product';
            $return = array();
            $return['status'] = true;
            $return['html'] = 'Imported Successfully!!!';
            return $return;
        }
    }
}*/