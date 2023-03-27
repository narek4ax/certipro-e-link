<?php
global $wpdb;
global $table_prefix;

$charset_collate = $wpdb->get_charset_collate();

$table_prefix = CPLINK_DB_PREFIX;
$items_tbl = $table_prefix . 'items';
$price_codes_tbl = $table_prefix . 'price_codes';
//$wpdb->query("CALL spPriceCalculation('para1','para2')");
$spPriceCalculationFile = CPLINK_PLUGIN_DIR . 'lib/data/spPriceCalculation.sql';
$procedure_sql = file_get_contents($spPriceCalculationFile);
$procedure_sql = str_replace(
    array('cps_items', 'cps_price_codes', 'customer_pricelevel'),
    array($items_tbl, $price_codes_tbl, 'customer_price_level'),
    $procedure_sql
);

//Test CALL spPriceCalculation(8983,02,0000028,1,2,'2022-04-10 00:00:00',@output)
$wpdb->query("DROP PROCEDURE IF EXISTS spPriceCalculation;");
$wpdb->query($procedure_sql);
//i_print($k);
