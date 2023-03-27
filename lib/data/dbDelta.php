<?php
global $wpdb;
global $table_prefix;

$charset_collate = $wpdb->get_charset_collate();

//Table structure for table `_customers`
$table_name = $table_prefix . 'customers';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `customer_name` varchar(30) DEFAULT '',
          `primary_shipto_code` varchar(4) DEFAULT '',
          `address_line1` varchar(30) DEFAULT '',
          `address_line2` varchar(30) DEFAULT '',
          `zip_code` varchar(10) DEFAULT '',
          `country_code` varchar(3) DEFAULT '',
          `city` varchar(20) DEFAULT '',
          `state` varchar(2) DEFAULT '',
          `telephone_no` varchar(17) DEFAULT '',
          `email_address` varchar(250) DEFAULT '',
          `contact_name` varchar(30) DEFAULT '',
          `contact_telephone_no1` varchar(17) DEFAULT '',
          `price_level` varchar(1) DEFAULT '',
          `tax_schedule` varchar(100) DEFAULT '',
          `current_balance` decimal(14,2) DEFAULT '0.00',
          `customer_discount_rate` decimal(13,3) DEFAULT '0.000',
          `terms_code` varchar(2) DEFAULT '',
          `credit_hold` tinyint(1) DEFAULT '0',
          `credit_limit` decimal(14,2) DEFAULT '0.00',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `ar_division_no` (`ar_division_no`,`customer_no`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_customers_shipto`
$table_name = $table_prefix . 'customers_shipto';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `shipto_code` varchar(4) NOT NULL,
          `name` varchar(30) DEFAULT '',
          `address1` varchar(30) DEFAULT '',
          `address2` varchar(30) DEFAULT '',
          `zip_code` varchar(10) DEFAULT '',
          `country_code` varchar(3) DEFAULT '',
          `city` varchar(20) DEFAULT '',
          `state` varchar(2) DEFAULT '',
          `telephone_no` varchar(17) DEFAULT '',
          `email_address` varchar(250) DEFAULT '',
          `contact_name` varchar(30) DEFAULT '',
          `contact_telephone_no1` varchar(17) DEFAULT '',
          `warehouse_code` varchar(255) DEFAULT '',
          `tax_schedule` varchar(100) DEFAULT '',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `ar_division_no` (`ar_division_no`,`customer_no`,`shipto_code`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_customers_tax_exemptions`
$table_name = $table_prefix . 'customers_tax_exemptions';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `shipto_code` varchar(4) NOT NULL,
          `tax_code` varchar(15) NOT NULL,
          `tax_exemption` varchar(20) DEFAULT '',
          `created_date` datetime DEFAULT NULL,
          `modified_date` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `tax_code` (`ar_division_no`,`customer_no`,`shipto_code`,`tax_code`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_customers`
$table_name = $table_prefix . 'users';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `user_id` varchar(5) NOT NULL,
          `first_name` varchar(20) DEFAULT '',
          `last_name` varchar(30) DEFAULT '',
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `shipto_code` varchar(30) DEFAULT '',
          `email_address` varchar(50) NOT NULL,
          `password` varchar(50) NOT NULL,
          `inactive_user` tinyint(1) DEFAULT '0',
          `view_balance` tinyint(1) DEFAULT '0',
          `view_invoice` tinyint(1) DEFAULT '0',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_id` (`user_id`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_items` - products
$table_name = $table_prefix . 'items';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `item_code` varchar(30) NOT NULL,
          `item_code_desc` text NOT NULL,
          `item_type` varchar(1) DEFAULT '',
          `product_line` varchar(4) NOT NULL,
          `procurement_type` varchar(30) DEFAULT '',
          `price_code` varchar(4) DEFAULT '',
          `tax_class` varchar(10) DEFAULT '',
          `image_file` varchar(30) DEFAULT '',
          `weight` varchar(10) DEFAULT '',
          `ebm_enabled` tinyint(1) DEFAULT '0',
          `sales_promotion_code` varchar(10) DEFAULT '',
          `sale_starting_date` date DEFAULT NULL,
          `sale_ending_date` date DEFAULT NULL,
          `sale_method` varchar(1) DEFAULT '',
          `standard_unit_price` decimal(16,6) DEFAULT '0.000000',
          `standard_unit_cost` decimal(16,6) DEFAULT '0.000000',
          `retail_price` decimal(16,6) DEFAULT '0.000000',
          `last_total_unit_cost` decimal(16,6) DEFAULT '0.000000',
          `sales_promotion_price` decimal(16,6) DEFAULT '0.000000',
          `sales_promotion_discount_percent` decimal(12,3) DEFAULT '0.000',
          `category1` varchar(10) DEFAULT '',
          `category2` varchar(10) DEFAULT '',
          `category3` varchar(10) DEFAULT '',
          `category4` varchar(10) DEFAULT '',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `item_code` (`item_code`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_product_lines`
$table_name = $table_prefix . 'product_lines';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `product_line` varchar(4) NOT NULL,
          `product_line_desc` varchar(25) DEFAULT '',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `product_line` (`product_line`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_item_warehouses`
$table_name = $table_prefix . 'item_warehouses';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `item_code` varchar(30) NOT NULL,
          `warehouse_code` varchar(3) NOT NULL,
          `quantity_on_hand` decimal(16,6) DEFAULT '0.000000',
          `quantity_available` decimal(16,6) DEFAULT '0.000000',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `item_code` (`item_code`,`warehouse_code`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_warehouses`
$table_name = $table_prefix . 'warehouses';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `warehouse_code` varchar(3) DEFAULT NULL,
          `warehouse_name` varchar(30) DEFAULT '',
          `warehouse_description` varchar(30) DEFAULT '',
          `address` varchar(30) DEFAULT '',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `warehouse_code` (`warehouse_code`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_custom_fields`
$table_name = $table_prefix . 'custom_fields';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `table_name` varchar(77) NOT NULL,
          `name` varchar(77) NOT NULL,
          `real_name` varchar(77) NOT NULL,
          `type` varchar(55) NOT NULL,
          `length` int(11) DEFAULT NULL,
          `default_value` varchar(255) DEFAULT '',
          `sage_data_field` varchar(55) DEFAULT '',
          `created_date` datetime DEFAULT NULL,
          `modified_date` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `table_field_name` (`table_name`,`name`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_payment_types`
$table_name = $table_prefix . 'payment_types';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `payment_type` varchar(5) NOT NULL,
          `payment_description` varchar(30) DEFAULT NULL,
          `payment_method` varchar(1) DEFAULT NULL,
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `payment_type` (`payment_type`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_transaction_payments`
$table_name = $table_prefix . 'transaction_payments';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `ar_division_number` varchar(2) NOT NULL,
          `customer_number` varchar(20) NOT NULL,
          `invoice_number` varchar(7) DEFAULT '',
          `invoice_type` varchar(2) DEFAULT '',
          `header_sequence_number` varchar(6) DEFAULT '',
          `transaction_date` datetime DEFAULT NULL,
          `sequence_number` varchar(6) DEFAULT '',
          `payment_reference` varchar(10) DEFAULT '',
          `check_number` varchar(10) DEFAULT '',
          `transaction_type` varchar(1) DEFAULT '',
          `payment_date` datetime DEFAULT NULL,
          `transaction_amount` decimal(12,2) DEFAULT '0.00',
          `created_date` datetime DEFAULT NULL,
          `modified_date` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `transaction_payments_key` (`ar_division_number`,`customer_number`,`invoice_number`,`invoice_type`,`transaction_date`,`sequence_number`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_price_codes`
$table_name = $table_prefix . 'price_codes';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `price_code_record` varchar(1) NOT NULL,
          `price_code` varchar(4) NOT NULL,
          `item_code` varchar(30) NOT NULL,
          `customer_price_level` varchar(1) NOT NULL,
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `pricing_method` varchar(1) NOT NULL,
          `break_quantity1` int(8) NOT NULL,
          `break_quantity2` int(8) NOT NULL,
          `break_quantity3` int(8) NOT NULL,
          `break_quantity4` int(8) NOT NULL,
          `break_quantity5` int(8) NOT NULL,
          `discount_markup1` decimal(16,6) NOT NULL,
          `discount_markup2` decimal(16,6) NOT NULL,
          `discount_markup3` decimal(16,6) NOT NULL,
          `discount_markup4` decimal(16,6) NOT NULL,
          `discount_markup5` decimal(16,6) NOT NULL,
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `price_code_record` (`price_code_record`,`price_code`,`item_code`,`customer_price_level`,`ar_division_no`,`customer_no`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_pricelevels_by_customer_price_codes`
$table_name = $table_prefix . 'pricelevels_by_customer_price_codes';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `product_line` varchar(4) NOT NULL,
          `price_code` varchar(4) NOT NULL,
          `effective_date` datetime NOT NULL,
          `end_date` datetime NOT NULL,
          `price_level` varchar(1) NOT NULL,
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `ar_division_no` (`ar_division_no`,`customer_no`,`product_line`,`price_code`,`effective_date`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_queue`
$table_name = $table_prefix . 'queue';
$sql = "CREATE TABLE IF NOT EXISTS  $table_name (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `web_sales_order_no` varchar(255) NOT NULL DEFAULT '',
            `status` tinyint(4) NOT NULL DEFAULT '0',
            `message` text NOT NULL,
            `export_count` int(11) DEFAULT '0',
            `created_time` datetime DEFAULT NULL,
            `update_time` datetime DEFAULT NULL,
            `active` tinyint(4) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_queue_status`
$table_name = $table_prefix . 'queue_status';
$sql = "CREATE TABLE IF NOT EXISTS  $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status` tinyint(4) NOT NULL,
            `name` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `status` (`status`)
        ) $charset_collate;";

dbDelta($sql);

$get_status = $wpdb->get_row("SELECT * FROM $table_name WHERE id = 1");
//i_print($get_status); exit;
if (!$get_status) {
    $sql = "INSERT INTO $table_name (`id`, `status`, `name`) VALUES
            (1, 0, 'New'),
            (2, 1, 'Sent'),
            (3, 2, 'Error on Send');";
    $wpdb->query($sql);
}

//Table structure for table `cps_import`
$table_name = $table_prefix . 'import';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `function_name` varchar(100) NOT NULL,
          `model_name` varchar(255) DEFAULT '',
          `description` varchar(255) DEFAULT NULL,
          `last_run_time` datetime DEFAULT NULL,
          `last_run_result` varchar(255) DEFAULT NULL,
          `last_source` varchar(10) NOT NULL,
          `status` varchar(150) DEFAULT NULL,
          `sort` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `function_name` (`function_name`)
        ) $charset_collate;";

dbDelta($sql);

//Table structure for table `_sales_orders`
$table_name = $table_prefix . 'sales_orders';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `sales_order_no` varchar(7) NOT NULL,
          `order_date` date NOT NULL,
          `order_type` varchar(10) DEFAULT NULL,
          `order_status` varchar(6) DEFAULT NULL,
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `invoice_no` varchar(7) DEFAULT '',
          `billto_name` varchar(30) DEFAULT '',
          `billto_address1` varchar(30) DEFAULT '',
          `billto_address2` varchar(30) DEFAULT '',
          `billto_zipcode` varchar(10) DEFAULT '',
          `billto_city` varchar(20) DEFAULT '',
          `billto_state` varchar(2) DEFAULT '',
          `shipto_code` varchar(4) DEFAULT '',
          `shipto_name` varchar(30) DEFAULT '',
          `shipto_address1` varchar(30) DEFAULT '',
          `shipto_address2` varchar(30) DEFAULT '',
          `shipto_zipcode` varchar(10) DEFAULT '',
          `shipto_city` varchar(20) DEFAULT '',
          `shipto_state` varchar(2) DEFAULT '',
          `shipping_code` varchar(30) DEFAULT '',
          `terms_code` varchar(30) DEFAULT '',
          `confirm_to` varchar(30) DEFAULT '',
          `email_address` varchar(250) DEFAULT '',
          `customer_po_no` varchar(15) DEFAULT '',
          `comment` varchar(30) DEFAULT '',
          `taxable_amount` decimal(12,2) DEFAULT '0.00',
          `nontaxable_amount` decimal(12,2) DEFAULT '0.00',
          `sales_tax_amount` decimal(12,2) DEFAULT '0.00',
          `freight_amount` decimal(12,2) DEFAULT '0.00',
          `discount_amount` decimal(12,2) DEFAULT '0.00',
          `payment_type` varchar(30) DEFAULT '',
          `check_number` varchar(30) DEFAULT '',
          `reference_number` varchar(30) DEFAULT '',
          `deposit_amount` decimal(12,2) DEFAULT '0.00',
          `total` decimal(12,2) DEFAULT '0.00',
          `net_order` decimal(13,2) DEFAULT '0.00',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          `web_sales_order_no` varchar(30) DEFAULT '',
          `web_user_id` varchar(5) DEFAULT '',
          PRIMARY KEY (`id`),
          UNIQUE KEY `sales_order_no` (`sales_order_no`),
          KEY `ar_division_no` (`ar_division_no`,`customer_no`),
          KEY `sales_order_no_2` (`sales_order_no`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_sales_order_lines`
$table_name = $table_prefix . 'sales_order_lines';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `sales_order_no` varchar(7) NOT NULL,
          `item_code` varchar(30) NOT NULL,
          `item_type` varchar(30) DEFAULT '',
          `item_code_desc` varchar(30) DEFAULT '',
          `quantity` decimal(16,6) DEFAULT '0.000000',
          `back_quantity` decimal(16,6) DEFAULT '0.000000',
          `unit_price` decimal(16,6) DEFAULT '0.000000',
          `line_discount_percent` decimal(9,3) DEFAULT '0.000',
          `extension_amt` decimal(12,2) DEFAULT '0.00',
          `comment` varchar(2048) DEFAULT '',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `sales_order_no` (`sales_order_no`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_shipping_methods`
$table_name = $table_prefix . 'shipping_methods';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `shipping_code` varchar(15) NOT NULL,
          `shipping_code_description` varchar(30) DEFAULT NULL,
          `freight_calculation_method` varchar(1) DEFAULT NULL,
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `shipping_code` (`shipping_code`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_shipping_methods_links`
$table_name = $table_prefix . 'shipping_methods_links';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `sage_shipping_code` varchar(15) NOT NULL,
          `mage_shipping_code` varchar(255) NOT NULL,
          `created_date` datetime DEFAULT NULL,
          `modified_date` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `shipping_code` (`sage_shipping_code`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_terms_code`
$table_name = $table_prefix . 'terms_code';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `terms_code` varchar(2) NOT NULL,
          `terms_code_description` varchar(30) DEFAULT NULL,
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `terms_code` (`terms_code`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_invoices`
$table_name = $table_prefix . 'invoices';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `invoice_no` varchar(7) NOT NULL,
          `header_seq_no` varchar(6) NOT NULL,
          `invoice_date` date NOT NULL,
          `invoice_type` varchar(15) NOT NULL,
          `ar_division_no` varchar(2) NOT NULL,
          `customer_no` varchar(20) NOT NULL,
          `sales_order_no` varchar(7) DEFAULT '',
          `billto_name` varchar(30) DEFAULT '',
          `billto_address1` varchar(30) DEFAULT '',
          `billto_address2` varchar(30) DEFAULT '',
          `billto_zipcode` varchar(10) DEFAULT '',
          `billto_city` varchar(20) DEFAULT '',
          `billto_state` varchar(2) DEFAULT '',
          `shipto_code` varchar(4) DEFAULT '',
          `shipto_name` varchar(30) DEFAULT '',
          `shipto_address1` varchar(30) DEFAULT '',
          `shipto_address2` varchar(30) DEFAULT '',
          `shipto_zipcode` varchar(10) DEFAULT '',
          `shipto_city` varchar(20) DEFAULT '',
          `shipto_state` varchar(2) DEFAULT '',
          `shipping_code` varchar(30) DEFAULT '',
          `terms_code` varchar(30) DEFAULT '',
          `confirm_to` varchar(30) DEFAULT '',
          `email_address` varchar(250) DEFAULT '',
          `customer_po_no` varchar(15) DEFAULT '',
          `comment` varchar(30) DEFAULT '',
          `taxable_amount` decimal(12,2) DEFAULT '0.00',
          `nontaxable_amount` decimal(12,2) DEFAULT '0.00',
          `sales_tax_amount` decimal(12,2) DEFAULT '0.00',
          `freight_amount` decimal(12,2) DEFAULT '0.00',
          `discount_amount` decimal(12,2) DEFAULT '0.00',
          `payment_type` varchar(30) DEFAULT '',
          `check_number` varchar(30) DEFAULT '',
          `reference_number` varchar(30) DEFAULT '',
          `deposit_amount` decimal(12,2) DEFAULT '0.00',
          `total` decimal(12,2) DEFAULT '0.00',
          `net_invoice` decimal(13,2) DEFAULT '0.00',
          `balance` decimal(13,2) DEFAULT '0.00',
          `payments_today` decimal(13,2) DEFAULT '0.00',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `invoice_no` (`invoice_no`,`header_seq_no`),
          KEY `invoice_no_2` (`invoice_no`,`header_seq_no`),
          KEY `ar_division_no` (`ar_division_no`,`customer_no`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_invoice_lines`
$table_name = $table_prefix . 'invoice_lines';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `invoice_no` varchar(7) NOT NULL,
          `header_seq_no` varchar(6) NOT NULL,
          `line_key` varchar(6) DEFAULT NULL,
          `item_code` varchar(30) NOT NULL,
          `item_type` varchar(30) DEFAULT NULL,
          `item_code_desc` varchar(30) DEFAULT '',
          `quantity` decimal(16,6) DEFAULT '0.000000',
          `unit_price` decimal(16,6) DEFAULT '0.000000',
          `line_discount_percent` decimal(9,3) DEFAULT '0.000',
          `extension_amt` decimal(12,2) DEFAULT '0.00',
          `comment` varchar(30) DEFAULT '',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `invoice_no` (`invoice_no`,`header_seq_no`,`line_key`),
          KEY `invoice_no_2` (`invoice_no`,`header_seq_no`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_invoice_serials`
$table_name = $table_prefix . 'invoice_serials';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `invoice_no` varchar(7) NOT NULL,
          `header_seq_no` varchar(6) NOT NULL,
          `line_key` varchar(6) NOT NULL,
          `lot_serial_number` varchar(50) NOT NULL,
          `quantity` decimal(16,6) NOT NULL,
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `invoice_no` (`invoice_no`,`header_seq_no`,`line_key`,`lot_serial_number`),
          KEY `invoice_no_2` (`invoice_no`,`header_seq_no`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_invoice_trackings`
$table_name = $table_prefix . 'invoice_trackings';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `invoice_no` varchar(7) NOT NULL,
          `header_seq_no` varchar(6) NOT NULL,
          `package_no` varchar(4) NOT NULL,
          `tracking_id` varchar(30) NOT NULL,
          `comment` varchar(30) DEFAULT '',
          `created_date` datetime NOT NULL,
          `modified_date` datetime NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `invoice_no_3` (`invoice_no`,`header_seq_no`,`package_no`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_synch_fields`
$table_name = $table_prefix . 'synch_fields';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `key` varchar(30) NOT NULL DEFAULT '',
          `name` varchar(30) NOT NULL DEFAULT '',
          `type` varchar(30) NOT NULL DEFAULT '',
          PRIMARY KEY (`id`),
          UNIQUE KEY `key` (`key`,`type`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);

//Table structure for table `_synch_log`
$table_name = $table_prefix . 'synch_log';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `table_name` varchar(50) NOT NULL,
          `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `source` varchar(50) DEFAULT NULL,
          `comment` varchar(500) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `table_name` (`table_name`,`create_date`,`source`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);


require_once(CPLINK_PLUGIN_DIR . 'lib/data/PriceCalculationProcedure.php');