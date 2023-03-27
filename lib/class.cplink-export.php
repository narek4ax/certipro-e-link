<?php

class CPLINK_EXPORT
{

    private static $initiated = false;

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
        if( !self::$initiated )
            self::check_export_action();

        self::$initiated = true;
    }


    private static function export_action()
    {
        if( is_user_logged_in() && is_admin() ) {
            $post_type = 'product';
        }
    }

}