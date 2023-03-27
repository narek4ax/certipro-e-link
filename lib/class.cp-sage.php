<?php

class CP_Sage
{

    protected $_scopeConfig;

    protected $headers = array();
    protected $errorNo = 0;
    protected $errorMessage = '';
    protected $api_url = "";
    protected $table_prefix = "";
    protected $current_date = "";
    protected $current_method_table = "";
    protected $is_cron = false;
    protected $import_source = 'sync';
    protected $import_type = '';
    protected $q_progress = array();
    protected $last_import_data = array();
    protected $users_collector = array();
    protected $purge_keys = array();
    protected $req_info = array(
        'success' => '',
        'counts' => array(
            'total' => '',
            'request' => ''
        )
    );
    protected $store_when_source = array(
        'purge',
        'global'
    );

    public function __construct($scopeConfig)
    {
        global $wpdb;
        $this->_scopeConfig = $scopeConfig;
        $this->table_prefix = CPLINK_DB_PREFIX;
        //$this->current_date = wp_date("Y-m-d h:i:s");
        $this->current_date = $wpdb->get_row('SELECT UTC_TIMESTAMP() as date');
        $this->current_date = $this->current_date->date;

        $this->headers = array();
        $this->errorNo = 0;
        $this->errorMessage = '';
        $this->api_url = "";
        $private_key = $this->isset_return($this->_scopeConfig, 'private_key');
        //i_print($private_key); exit;
    }


    function getStoreWhenSource()
    {
        return $this->store_when_source;
    }

    function ItsCron()
    {
        $this->is_cron = true;
    }

    function getErrorNo()
    {
        return $this->errorNo;
    }

    function getError()
    {
        return $this->errorMessage;
    }

    function getQProgress()
    {
        return $this->q_progress;
    }

    function getRequestInfo()
    {
        return $this->req_info;
    }

    function getCurrentMethodtable()
    {
        return $this->current_method_table;
    }


    function getAPIError()
    {
        return array(
            'errorNo' => $this->errorNo,
            'errorMessage' => $this->errorMessage
        );
    }

    function setImportType($import_type)
    {
        return $this->import_type = $import_type;
    }

    function setImportSource($import_source)
    {
        return $this->import_source = $import_source;
    }

    function getLastImportData()
    {
        $import_statuses = CPLINK::$import_statuses;
        $import_run_results = CPLINK::$import_run_results;

        $last_import_data = $this->last_import_data;
        $last_import_data['status_txt'] = $import_statuses[$last_import_data['status']];
        $last_import_data['run_result_txt'] = $import_run_results[$last_import_data['run_result']];

        return $last_import_data;
    }

    function isset_return($array = array(), $key, $default = '')
    {
        if (!empty($array) && is_array($array) && isset($array[$key])) {
            return $array[$key];
        }
        return $default;
    }

    function initHeaders($data)
    {
        $key_private = $this->isset_return($this->_scopeConfig, 'private_key');
        $key_public = $this->isset_return($this->_scopeConfig, 'public_key');
        $hash = hash_hmac('sha256', $data, $key_private);
        $this->headers = array(
            'Content-Type: application/json',
            'C-Public-Key: ' . $key_public,
            'C-Content-Hash: ' . $hash
        );
    }

    function ModifiedFromInProgress($modified_from, $endpoint, $in_progress = true)
    {
        $option_key = str_replace('/', '_', $endpoint);
        $last_import_data = get_option('clink_' . $option_key . '_last_import');

        if (!$modified_from) {
            $modified_from = '';
            if ($last_import_data && is_array($last_import_data)) {
                $last_import_date = $last_import_data['date'];
                $modified_from = date("Ymdhis", strtotime($last_import_date));
            }
        } elseif ($modified_from == '-1') {
            $modified_from = '';
            switch ($endpoint) {
                case 'salesorders':
                case 'invoices':
                    $cut_of_months = CPLINK::get_module_option($endpoint . '_cut_of_months');
                    if ($cut_of_months && is_numeric($cut_of_months)) {
                        $modified_from = date('Ymdhis', strtotime("-" . $cut_of_months . " month"));
                    }
                    break;

                case 'cashreceipts':
                case 'cashreceipts/history':
                    $cut_of_months = CPBP::get_scopConfig('cash_receipts_cut_of_months');
                    if ($cut_of_months && is_numeric($cut_of_months)) {
                        $modified_from = date('Ymdhis', strtotime("-" . $cut_of_months . " month"));
                    }
                    break;

                default:
                    $modified_from = '';
                    break;
            }
        }
        if ($in_progress) {
            //Start / In Progress status
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '0',
                'run_result' => '0',
                'in_progress' => '1'
            );
            update_option('clink_' . $option_key . '_last_import', $last_import_data);
        }

        return $modified_from;
    }

    function sendRequest($endpoint, $data = array(), $timeout = -1, $conntimeout = -1, $verb = "GET", $page = 0, $limit = 20, $modified_from = '')
    {
        if( $this->import_source == 'purge' ) {
            $except_endpoints = array('customers/taxexemptions', 'pricelevels/customerpricecodes', 'termscode', 'paymenttypes', 'shippingmethods', 'warehouses', 'cashreceipts/history');
            switch ($endpoint){
                case 'invoices/history':
                    $endpoint = 'invoiceshistory';
                    break;
                case 'salesorders/history':
                    $endpoint = 'salesordershistory';
                    break;
                case 'customers/addresses':
                    $endpoint = 'customershipto';
                    break;
                case 'customers/taxexemptions':
                    break;
                case 'invoices/trackings':
                    $endpoint = 'invoicetrackings';
                    break;
                case 'cashreceipts/history':
                    //$endpoint = 'cashreceipthistory';
                    break;
                default:
                    $endpoint;
            }

            if( in_array($endpoint, $except_endpoints) ){
                //$this->import_source = 'global';
                $modified_from = '';
            } else {
                $endpoint = $endpoint.'/keys';
            }
        }

        $this->req_info = array(
            'success' => '',
            'counts' => array(
                'total' => '',
                'request' => ''
            )
        );

        if (empty($this->api_url)) {
            $this->api_url = $this->isset_return($this->_scopeConfig, 'api_url');
            //need to check, if no slash then add after url
        }
        if (substr($this->api_url, -1) != '/')
            $this->api_url .= '/';

        $request = array(
            "page" => $page,
            "limit" => $limit,
            "modified_from" => $modified_from //Use for searching changed records (Format is yyyyMMdd or yyyyMMddHHmmss)
        );
        if( $data )
            $request['data'] = $data;

        $data = json_encode($request);
        $url = $this->api_url . $endpoint;
        $this->initHeaders($data);
        $this->errorNo = 0;
        $this->errorMessage = '';


        $ch = $this->getCURLObject($url, $data, $timeout, $conntimeout, $verb);
        $result = curl_exec($ch);
        $this->errorNo = curl_errno($ch);
        if ($this->errorNo) {
            $this->errorMessage = curl_error($ch);
        } else if (isset(curl_getinfo($ch)['http_code']) && curl_getinfo($ch)['http_code'] != 200) {
            // $this->errorNo = 1;
        }

        curl_close($ch);
        $result = json_decode($result);

        //Store last request status and counts
        if (isset($result->success))
            $this->req_info['success'] = $result->success;
        if (isset($result->counts))
            $this->req_info['counts'] = (array)$result->counts;

        return $result;
    }

    function getCURLObject($url, $data, $timeout = -1, $conntimeout = -1, $verb = "POST")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($ch, CURLOPT_PORT, '5901'); //5901 //parse_url($url, PHP_URL_PORT)

        $defTimeout = $this->isset_return($this->_scopeConfig, 'api_conn_timeout');
        if ($timeout > -1) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        } else if (is_numeric($defTimeout) && $defTimeout > -1) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $defTimeout);
        }
        if ($conntimeout > -1) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $conntimeout);
        } else if (is_numeric($defTimeout) && $defTimeout > -1) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $defTimeout);
        }

        if ($verb == "DELETE") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
        }

        if (!empty($data)) {
            if ($verb == "POST") {
                curl_setopt($ch, CURLOPT_POST, 1);
            } elseif ($verb == "PUT") {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        return $ch;
    }

    function testConnection()
    {
        $data = array();
        $customers = $this->sendRequest('customers', $data);

        $result = array();

        if ($this->errorNo) {
            $message = 'ERROR(' . $this->errorNo . '):  ' . $this->errorMessage;
            $type = 'error';
            add_settings_error('cplink_settings', 'cplink_api_isse', $message, $type);
        } else {
            $result = array();
            if ($customers->success) {
                $result = $customers->data;
            }
        }

        return $result;
    }

    function get_IP_info()
    {
        $data = array();
        $url = 'https://ipinfo.io/' . $_SERVER['REMOTE_ADDR'] . '/json';
        echo 'Get Results from - ' . $url;
        $ch = $this->getCURLObject($url, $data, -1, -1, "GET");
        $result = curl_exec($ch);
        $this->errorNo = curl_errno($ch);
        if ($this->errorNo) {
            $this->errorMessage = curl_error($ch);
        } else if (isset(curl_getinfo($ch)['http_code']) && curl_getinfo($ch)['http_code'] != 200) {
            // $this->errorNo = 1;
        }

        return $result;
    }

    function getCustomers($customer_number = null, $ar_division_number = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false, $fields = '*')
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'customers';
        $endpoint = 'customers';
        $t_keys = array('ar_division_no', 'customer_no');
        //ar_division_no_for_existing_user

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $where = '';
            $where_data = array('LOWER(customer_no)' => "LOWER('" . $customer_number . "')", 'ar_division_no' => $ar_division_number);
            foreach ($where_data as $where_key => $where_val) {
                if ($where_val) {
                    if (!empty($where))
                        $where .= ' AND ';
                    $where .= $where_key . ' = ' . strval($where_val);
                }
            }
            $result_data = $this->get_local_data($table_name, $page, $limit, $where, $fields);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        /*if ($customer_number == null)
            $customer_number = CPLINK::get_module_option('customer_no_for_existing_user');
        if ($ar_division_number == null)
            $ar_division_number = CPLINK::get_module_option('ar_division_no_for_existing_user');*/

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('customer_number' => $customer_number, 'ar_division_number' => $ar_division_number);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        //i_print($customers); exit;

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {

                        if ( in_array($import_source, $this->store_when_source ) ) {
                            $result[] = $result_item;
                            $t_data = array(
                                'ar_division_no' => $result_item->ar_division_number,
                                'customer_no' => $result_item->customer_number,
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            if (($ar_division_number == null && $customer_number == null)
                                || ($result_item->ar_division_number == $ar_division_number && $result_item->customer_number == $customer_number)) {

                                $t_data = array(
                                    'ar_division_no' => $result_item->ar_division_number,
                                    'customer_no' => $result_item->customer_number,
                                    'customer_name' => $result_item->customer_name,
                                    'primary_shipto_code' => $result_item->primary_shipto_code,
                                    'address_line1' => $result_item->address1,
                                    'address_line2' => $result_item->address2,
                                    'zip_code' => $result_item->zip,
                                    'country_code' => CPLINK::get_country_iso2($result_item->country),
                                    'city' => $result_item->city,
                                    'state' => $result_item->state,
                                    'telephone_no' => $result_item->phone,
                                    'email_address' => $result_item->email,
                                    'contact_name' => $result_item->contact_name,
                                    'contact_telephone_no1' => $result_item->contact_phone,
                                    'price_level' => $result_item->price_level,
                                    'tax_schedule' => $result_item->tax_schedule,
                                    'current_balance' => $result_item->current_balance,
                                    'customer_discount_rate' => $result_item->discount_rate,
                                    'terms_code' => $result_item->terms_code,
                                    'credit_hold' => ($result_item->credit_hold) ? $result_item->credit_hold : 0,
                                    'credit_limit' => $result_item->credit_limit
                                );
                                $result[] = $t_data;

                                $t_result = $this->insert_db($table_name, $t_data, $t_keys); //email_address
                            }
                        }

                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        //Prepare Data to append if needed
        //$customersAddresses = $this->getCustomersAddresses(null, null, null, 0, -1, $modified_from, true);
        //$taxExemptions = $this->getCustomersTaxExemptions(null, null, 0, -1, $modified_from, true);

        //Update Customers billing and Shipping data
        if ($import_source != 'purge') {
            $this->updateUsersAddresses($result);
        }


        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getCustomersTaxExemptions($customer_number = null, $ar_division_number = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'customers_tax_exemptions';
        $endpoint = 'customers/taxexemptions';
        $option_key = str_replace('/', '_', $endpoint);
        $t_keys = array('ar_division_no', 'customer_no', 'shipto_code', 'tax_code');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        /*if ($customer_number == null)
            $customer_number = CPLINK::get_module_option('customer_no_for_existing_user');
        if ($ar_division_number == null)
            $ar_division_number = CPLINK::get_module_option('ar_division_no_for_existing_user');*/

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('customer_number' => $customer_number, 'ar_division_number' => $ar_division_number);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
        //i_print($tax_exemptions); exit;

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {

                        if ( in_array($import_source, $this->store_when_source ) ) {
                            $result[] = $result_item;
                            $t_data = array(
                                'ar_division_no' => $result_item->ar_division_number,
                                'customer_no' => $result_item->customer_number,
                                'shipto_code' => $result_item->shipto_code,
                                'tax_code' => $result_item->tax_code
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            if (($ar_division_number == null && $customer_number == null)
                                || ($result_item->ar_division_number == $ar_division_number && $result_item->customer_number == $customer_number)) {

                                $t_data = array(
                                    'ar_division_no' => $result_item->ar_division_number,
                                    'customer_no' => $result_item->customer_number,
                                    'shipto_code' => $result_item->shipto_code,
                                    'tax_code' => $result_item->tax_code,
                                    'tax_exemption' => $result_item->tax_exemption_number,
                                );
                                $result[] = $t_data;

                                $t_result = $this->insert_db($table_name, $t_data, $t_keys); //email_address
                            }
                        }

                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    /*
     *
     * Create Customers
     * ar_division_number - required
     * customer_number - required
     * customer_name, address1, address2, city, state, zip, country, phone, email, price_level, tax_schedule, comments - null
     *
     * */
    public function createCustomers($data = array())
    {
        $result = array();
        $endpoint = 'x';

        //if (!empty($data['ar_division_number']) && !empty($data['customer_number'])) {
        $createCustomerResult = $this->sendRequest('customers/create', $data, -1, -1, "POST");

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($createCustomerResult->success) {
                $result = $createCustomerResult->data;
            }
        }

        /*} else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "ar_division_number and customer_number are required!"];
        }*/

        return $result;
    }

    /*
     *
     * Update Customers
     * ar_division_number - required
     * customer_number - required
     * customer_name, address1, address2, city, state, zip, country, phone, email, price_level, tax_schedule, comments - null
     * primary_shipto_code
     * */
    public function updateCustomers($data = array())
    {
        $result = array();

        if (!empty($data['ar_division_number']) && !empty($data['customer_number'])) {
            $createCustomerResult = $this->sendRequest('customers/update', $data, -1, -1, "POST");

            if ($this->errorNo) {
                //i_print('ERROR: ' . $this->errorNo);
                //i_print($this->errorMessage);
            } else {
                $result = array();
                if ($createCustomerResult->success) {
                    $result = $createCustomerResult->data;
                }
            }

        } else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "ar_division_number and customer_number are required!"];
        }

        return $result;
    }

    /*
     *
     * Customers Addresses
     * ar_division_number
     * customer_number
     * shipto_code
     *
     * */
    function getCustomersAddresses($ar_division_number = null, $customer_number = null, $shipto_code = null, $page = 0, $limit = 20, $modified_from = '-1')
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'customers_shipto';
        $endpoint = 'customers/addresses';
        $option_key = str_replace('/', '_', $endpoint);
        $t_keys = array('ar_division_no', 'customer_no', 'shipto_code');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        /*if( $customer_number == null )
            $customer_number = CPLINK::get_module_option( 'customer_no_for_existing_user');
        if( $ar_division_number == null )
            $ar_division_number = CPLINK::get_module_option( 'ar_division_no_for_existing_user');*/

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('ar_division_number' => $ar_division_number, 'customer_number' => $customer_number, 'shipto_code' => $shipto_code);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {

                        if ( in_array($import_source, $this->store_when_source ) ) {
                            $result[] = $result_item;
                            $t_data = array(
                                'ar_division_no' => $result_item->ar_division_number,
                                'customer_no' => $result_item->customer_number,
                                'shipto_code' => $result_item->shipto_code,
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            if (($ar_division_number == null && $customer_number == null && $shipto_code == null)
                                || ($result_item->ar_division_number == $ar_division_number && $result_item->customer_number == $customer_number && $result_item->shipto_code == $shipto_code)
                            ) {

                                $t_data = array(
                                    'ar_division_no' => $result_item->ar_division_number,
                                    'customer_no' => $result_item->customer_number,
                                    'shipto_code' => $result_item->shipto_code,
                                    'name' => $result_item->name,
                                    'address1' => $result_item->address1,
                                    'address2' => $result_item->address2,
                                    'zip_code' => $result_item->zip,
                                    'country_code' => $result_item->country,
                                    'city' => $result_item->city,
                                    'state' => $result_item->state,
                                    'telephone_no' => $result_item->phone,
                                    'email_address' => $result_item->email,
                                    'contact_name' => $result_item->contact_name,
                                    'contact_telephone_no1' => $result_item->contact_phone,
                                    'warehouse_code' => $result_item->price_level,
                                    'tax_schedule' => $result_item->tax_schedule
                                );
                                $result[] = $t_data;
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                            }
                        }

                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }
        if ($import_source != 'purge') {
            $update_bill_ship = $this->updateUsersAddresses(null, $result);
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function updateUsersAddresses($customers_data = null, $customers_shipto_data = null)
    {
        $users_collector = $this->users_collector;
        $update_customers_for = array();
        if ($customers_data) { //echo 'Customers Import ---';
            $req_status = $this->isset_return($customers_data, 'status');
            if (!$req_status) {
                $update_customers_for = array_merge($update_customers_for, $customers_data);
            }
        }
        if ($customers_shipto_data) { //echo 'Customers Addresses Import ---';
            $req_status = $this->isset_return($customers_shipto_data, 'status');
            if (!$req_status) {
                $update_customers_for = array_merge($update_customers_for, $customers_shipto_data);
            }
        }

        if (count($update_customers_for))
            foreach ($update_customers_for as $shipto_data) {
                $shipto_data = (array)$shipto_data;
                $collector_key = $shipto_data['customer_no'] . '_' . $shipto_data['ar_division_no'];

                if (isset($users_collector[$collector_key]))
                    continue;

                $args = array(
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'relation' => 'AND',
                            array(
                                'key' => 'cp_customer_no',
                                'value' => $shipto_data['customer_no'],
                                'compare' => "=",
                            ),
                            array(
                                'key' => 'cp_ar_division_no',
                                'value' => $shipto_data['ar_division_no'],
                                'compare' => "=",
                            ),
                        )
                    )
                );
                $users = get_users($args);

                $users_collector[$collector_key] = 1; //i_print('Updating info for $collector_key = '.$collector_key);

                if (!empty($users)) {
                    foreach ($users as $user_id) {
                        CPLINK::woocommerce_registration_custom_fields_save($user_id, $shipto_data['customer_no'], $shipto_data['ar_division_no']);
                    }
                }
            }
        $this->users_collector = $users_collector;
    }


    /*
     *
     * Create Customers Addresses
     * shipto_code - required
     * customer_number - required
     * ar_division_number - required
     * name, address1, address2, city, state, zip, country, phone, email, warehouse_code, tax_schedule - null
     *
     * */
    function createCustomersAddresses($data = array(), $modified_from = '')
    {
        $result = array();

        if (!empty($data['shipto_code']) && !empty($data['customer_number']) && !empty($data['ar_division_number'])) {

            $createCustomersAddressesResult = $this->sendRequest('customers/addresses/create', $data, -1, -1, "POST", 0, 20, $modified_from);

            if ($this->errorNo) {
                //i_print('ERROR: ' . $this->errorNo);
                //i_print($this->errorMessage);
            } else {
                $result = array();
                if ($createCustomersAddressesResult->success) {
                    $result = $createCustomersAddressesResult->data;
                }
            }

        } else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "ar_division_number, shipto_code and customer_number are required!"];
        }

        return $result;
    }

    /*
     *
     * Update Customers Addresses
     * customer_number - required
     * ar_division_number - required
     * shipto_code - required
     * address1 - required
     * city - required
     * state - required
     * zip - required
     * country - required
     * name, address2, phone, email, warehouse_code, tax_schedule - null
     *
     * */
    function updateCustomersAddresses($data = array())
    {
        $result = array();

        if (!empty($data['customer_number']) && !empty($data['ar_division_number']) && !empty($data['shipto_code']) && !empty($data['address1'])
            && !empty($data['city']) && !empty($data['state']) && !empty($data['zip']) && !empty($data['country'])) {

            $updateCustomersAddressesResult = $this->sendRequest('customers/addresses/update', $data, -1, -1, "POST");

            if ($this->errorNo) {
                //i_print('ERROR: ' . $this->errorNo);
                //i_print($this->errorMessage);
            } else {
                $result = array();
                if ($updateCustomersAddressesResult->success) {
                    $result = $updateCustomersAddressesResult->data;
                }
            }

        } else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "ar_division_number, shipto_code and customer_number are required!"];
        }

        return $result;
    }

    /*
    *
    * Delete Customers Addresses
    * customer_number - required
    * ar_division_number - required
    * shipto_code - required
    * name, address2, phone, email, warehouse_code, tax_schedule - null
    *
    * */
    function deleteCustomersAddresses($customer_number = null, $ar_division_number = null, $shipto_code = null, $page = 0, $limit = 20, $modified_from)
    {
        $result = array();

        if (!empty($customer_number) && !empty($ar_division_number) && !empty($shipto_code)) {
            $data = array('ar_division_number' => $ar_division_number, 'customer_number' => $customer_number, 'shipto_code' => $shipto_code);

            $deleteCustomersAddressesResult = $this->sendRequest('customers/addresses/delete', $data, -1, -1, "POST", $page, $limit, $modified_from);

            if ($this->errorNo) {
                //i_print('ERROR: ' . $this->errorNo);
                //i_print($this->errorMessage);
            } else {
                $result = array();
                if ($deleteCustomersAddressesResult->success) {
                    $result = $deleteCustomersAddressesResult->data;
                }
            }

        } else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "ar_division_number, shipto_code and customer_number are required!"];
        }

        return $result;
    }

    function getProducts($item_code = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        //i_print( CPLINK::get_country_iso3('AM') ); exit; //Y-m-d h:i:s //'2020-01-01 00:00:00';//
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table = $table_prefix . 'items';
        $endpoint = 'products';
        $t_keys = array('item_code');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, 0, '-1');
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $data = array('item_code' => $item_code);

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
        //i_print($response_data); //exit;

        //$last_import_data = get_option('clink_' . $endpoint . '_last_import', true);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;

                if (count($result_data))
                    foreach ($result_data as $result_item) {

                        if ( in_array($import_source, $this->store_when_source ) ) {
                            $result[] = $result_item;
                            $t_data = array(
                                'item_code' => $result_item->item_code,
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            if ($result_item->item_type == '1') { //&& $result_item->ebm_enabled
                                $result[] = $result_item;
                                $t_data = array(
                                    'item_code' => $result_item->item_code,
                                    'item_code_desc' => $result_item->item_code_description,
                                    'item_type' => $result_item->item_type,
                                    'product_line' => $result_item->product_line,
                                    'procurement_type' => $result_item->procurement_type,
                                    'price_code' => $result_item->price_code,
                                    'tax_class' => $result_item->tax_class,
                                    'image_file' => $result_item->image_file,
                                    'weight' => $result_item->weight,
                                    'ebm_enabled' => $result_item->ebm_enabled,
                                    'sales_promotion_code' => $result_item->sales_promotion_code,
                                    'sale_starting_date' => $result_item->sale_date_start,
                                    'sale_ending_date' => $result_item->sale_date_end,
                                    'sale_method' => $result_item->sale_method,
                                    'standard_unit_price' => $result_item->standard_unit_price,
                                    'standard_unit_cost' => $result_item->standard_unit_cost,
                                    'retail_price' => $result_item->retail_price,
                                    'last_total_unit_cost' => $result_item->last_total_unit_cost,
                                    'sales_promotion_price' => $result_item->sales_promotion_price,
                                    'sales_promotion_discount_percent' => $result_item->sales_promotion_discount_percent,
                                    'category1' => $result_item->category1,
                                    'category2' => $result_item->category2,
                                    'category3' => $result_item->category3,
                                    'category4' => $result_item->category4,
                                );
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys );
                            }
                        }

                    }
                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        if ($local && !$result['status']) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                foreach ($result_data as $result_item) {
                    $result[$result_item->item_code] = $result_item->product_line_desc;
                }
            }
        }

        $this->last_import_data = $last_import_data;

        return $result;
    }

    public function getProductLines($product_line = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table = $table_prefix . 'product_lines';
        $endpoint = 'productlines';
        $t_keys = array('product_line');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        $result = array();

        if ($local) {
            $result_data = $this->get_local_data($table_name, 0, '-1');
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }
        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('product_line' => $product_line);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;

                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        $result[$result_item->product_line] = $result_item->product_line_description;
                        if ( in_array($import_source, $this->store_when_source ) ) {
                            $result[] = $result_item;
                            $t_data = array(
                                'product_line' => $result_item->product_line,
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            $t_data = array(
                                'product_line' => $result_item->product_line,
                                'product_line_desc' => $result_item->product_line_description
                            );
                            $t_result = $this->insert_db($table_name, $t_data, $t_keys );
                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                foreach ($result_data as $result_item) {
                    $result[$result_item->product_line] = $result_item;
                }
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    public function get_local_data($table_name, $page = 0, $limit = -1, $where = '', $fields = '*')
    {
        global $wpdb;
        $sql_limit = "";
        //will return $local and fresh data
        if ($limit > 0) {
            $offset = 0;
            if ($page > 0)
                $offset = ($page - 1) * $limit;
            $sql_limit = "LIMIT $limit OFFSET $offset";
        }
        $where_sql = 'WHERE 1=1';
        if ($where)
            $where_sql .= ' AND ' . $where;

        $sql = "SELECT $fields FROM $table_name $where_sql $sql_limit";
        //i_print($sql);

        $local_results = $wpdb->get_results($sql);
        return $local_results;
    }

    public function db_empty_table($table_name)
    {
        global $wpdb;
        $delete = $wpdb->query("TRUNCATE TABLE $table_name");
    }

    public function drop_temp_table($table_name='')
    {
        global $wpdb;
        if( !$table_name )
            $table_name = $this->getCurrentMethodtable();
        $temp_table_name = 'temp_'.$table_name;

        $delete = $wpdb->query("DROP TABLE IF EXISTS $temp_table_name");
    }

    public function get_temp_table($table_name)
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $tmp_table_name = 'temp_'.$table_name;

        $sql = "CREATE TABLE IF NOT EXISTS $tmp_table_name LIKE $table_name";
        dbDelta($sql);

        return $tmp_table_name;
    }

    public function store_temp_data($table_name = '', $t_data, $check_keys = array()){

        if( !$table_name )
            $table_name = $this->getCurrentMethodtable();
        $tmp_table_name = $this->get_temp_table($table_name);

        $t_result = $this->insert_db($tmp_table_name, $t_data, $check_keys);
    }

    public function PurgeData($table_name='')
    {
        global $wpdb;
        $table_prefix = $this->table_prefix;
        $table_name = $this->getCurrentMethodtable();
        $import_type = $this->import_type; //i_print($import_type);
        $exclude_types = array('invoices_history', 'salesorders_history');

        if( in_array( $import_type, $exclude_types)  ){
            return;
        }

        $temp_table_name = 'temp_'.$table_name;
        $purge_keys = $this->purge_keys;
        $purge_tables = array($table_name);

        switch ($import_type){
            case 'invoices':
                array_push($purge_tables, $table_prefix.'invoice_lines', $table_prefix.'invoice_serials', $table_prefix.'invoice_trackings');
                break;
            case 'customers':
                array_push($purge_tables, $table_prefix.'customers_shipto', $table_prefix.'customers_tax_exemptions');
                break;
        }

        foreach ($purge_tables as $purge_table){ //i_print($purge_table);
            $sql = "DELETE main_tbl FROM $purge_table AS main_tbl WHERE 1=1 ";
            $where = "";
            $i=0;

            if( count($purge_keys) ){
                foreach ($purge_keys as $purge_key => $unused) {
                    if( $i == 0 ){
                        $sql.= " AND `$purge_key` NOT IN (SELECT `$purge_key` FROM $temp_table_name as purg_tbl WHERE 1=1 ";
                    } else {
                        $where.= " AND main_tbl.$purge_key = purg_tbl.$purge_key ";
                    }
                    /*if( $import_type == 'invoices_history' ){
                        $sql.= " AND purg_tbl.header_seq_no != '' ";
                    } elseif($import_type == 'invoices'){
                        $sql.= " AND purg_tbl.header_seq_no = '' ";
                    }*/
                    $i++;
                }
                $sql.= $where.")";

                if( $import_type == 'invoices_history' ){
                    $sql.= " AND main_tbl.header_seq_no != '' ";
                } elseif($import_type == 'invoices'){
                    $sql.= " AND main_tbl.header_seq_no = '' ";
                }

                if( $import_type == 'salesorders_history' ){
                    /*"order_type": "",
                    "order_status": "C",*/
                    $sql.= " AND main_tbl.order_status == 'C' ";
                } elseif($import_type == 'salesorders'){
                    $sql.= " AND main_tbl.order_status != 'C' "; //i_print($sql);
                }
            }

            $q_result = $wpdb->query($sql); //i_print($sql); //i_print( $wpdb->get_results($sql) );
        }
        //i_print('$import_type='.$import_type);
        //if($import_type != 'invoices')
        $delete = $wpdb->query("DROP TABLE IF EXISTS $temp_table_name"); //$this->drop_temp_table();
    }

    public function insert_db($table_name = '', $t_data = array(), $check_keys = array())
    {
        global $wpdb;
        if (!$table_name || !is_array($t_data) || !count($t_data))
            return false;
        $db_debug = false;
        $current_date = $this->current_date;
        $t_data['modified_date'] = $current_date;
        if (count($t_data)) {
            $find_id = '';
            $check_where = '';
            if ($check_keys != null) {
                if (!empty($check_keys)) {
                    foreach ($check_keys as $check_key) {
                        if( !isset($t_data[$check_key]) )
                            continue;
                        $check_value = $t_data[$check_key];
                        if (!empty($check_value)) {
                            if ($check_where)
                                $check_where .= "AND";
                            $check_where .= " `$check_key` = '$check_value' ";
                        }
                    }
                } else {
                    $check_key = $this->array_key_first($t_data);
                    $check_value = $t_data[$check_key];
                    $check_where = "`$check_key` = '$check_value'";
                }

                if ($check_where) {
                    $sql = "SELECT `id` FROM `$table_name` WHERE $check_where";

                    $find_id = $wpdb->get_row($sql, ARRAY_A);
                }
            }

            //i_print( $sql ); //exit;
            //i_print( $find_id ); //exit;
            if ($find_id) { //echo $sql;i_print( $find_id ); exit;
                $id = $find_id['id'];
                $t_result = $wpdb->update(
                    $table_name,
                    $t_data,
                    array('id' => $id),
                    '%s'
                );
                if ($db_debug) {
                    i_print('here -> update' . ' $find_id=' . $find_id);
                    i_print($table_name);
                    i_print($t_data);
                    i_print($t_result);
                    $query = htmlspecialchars($wpdb->last_query, ENT_QUOTES);
                    echo $query;
                }
            } else {
                $t_data['created_date'] = $current_date;
                $t_result = $wpdb->insert($table_name, $t_data, '%s');

                if ($db_debug) {
                    i_print('here -> insert');
                    i_print($table_name);
                    i_print($t_data);
                    i_print($t_result);
                    $query = htmlspecialchars($wpdb->last_query, ENT_QUOTES);
                    echo $query;
                }
            }
            if ($db_debug && $wpdb->last_error !== '') :
                $str = htmlspecialchars($wpdb->last_result, ENT_QUOTES);
                $query = htmlspecialchars($wpdb->last_query, ENT_QUOTES);

                print "<div id='error'>
        <p class='wpdberror'><strong>WordPress database error:</strong> [$str]<br />
        <code>$query</code></p>
        </div>";
            endif;
            return $t_result;
            /**/
        }
    }

    public function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }

    public function getInventories($item_code = null, $warehouse_code = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'item_warehouses';
        $endpoint = 'inventory';
        $option_key = str_replace('/', '_', $endpoint);
        $t_keys = array('item_code', 'warehouse_code');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        $result = array();
        if ($local) {
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        /*if ($customer_number == null)
            $customer_number = CPLINK::get_module_option('customer_no_for_existing_user');
        if ($ar_division_number == null)
            $ar_division_number = CPLINK::get_module_option('ar_division_no_for_existing_user');*/
        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('item_code' => $item_code, 'warehouse_code' => $warehouse_code);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;

                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        $result[] = $result_item;
                        if ( in_array($import_source, $this->store_when_source ) ) {
                            //$delete = $this->db_empty_table($table_name);
                            $result[] = $result_item;
                            $t_data = array(
                                'item_code' => $result_item->item_code,
                                'warehouse_code' => $result_item->warehouse_code,
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            $t_data = array(
                                'item_code' => $result_item->item_code,
                                'warehouse_code' => $result_item->warehouse_code,
                                'quantity_on_hand' => $result_item->quantity_on_hand,
                                'quantity_available' => $result_item->quantity_available,
                            );
                            $t_result = $this->insert_db($table_name, $t_data, $t_keys );//wp_date("Y-m-d h:i:s"),
                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }


        $this->last_import_data = $last_import_data;
        return $result;
    }

    public function getSalesOrders($history = false, $ar_division_number = null, $customer_number = null, $sales_order_number = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'sales_orders';
        $endpoint = 'salesorders';
        $t_keys = array('sales_order_no'); //, 'ar_division_no', 'customer_no'
        if ($history)
            $endpoint = 'salesorders/history';

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $option_key = str_replace('/', '_', $endpoint);

        /*if ($customer_number == null)
            $customer_number = CPLINK::get_module_option('customer_no_for_existing_user');
        if ($ar_division_number == null)
            $ar_division_number = CPLINK::get_module_option('ar_division_no_for_existing_user');*/
        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('ar_division_number' => $ar_division_number, 'customer_number' => $customer_number, 'sales_order_number' => $sales_order_number);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
        //$sales_history_data = $this->getSalesOrdersHistory($ar_division_number, $customer_number, $sales_order_number, $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;

                if (!empty($sales_history_data))
                    $result_data = array_merge($result_data, $sales_history_data);


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ( in_array($import_source, $this->store_when_source ) ) {
                            //$delete = $this->db_empty_table($table_name);
                            $result[] = $result_item;
                            $t_data = array(
                                'sales_order_no' => $result_item->sales_order_number
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            if (($ar_division_number == null && $customer_number == null)
                                || ($result_item->ar_division_number == $ar_division_number && $result_item->customer_number == $customer_number)) {

                                $t_data = array(
                                    'sales_order_no' => $result_item->sales_order_number,
                                    'order_date' => $result_item->order_date,
                                    'order_type' => $result_item->order_type,
                                    'order_status' => $result_item->order_status,
                                    'ar_division_no' => $result_item->ar_division_number,
                                    'customer_no' => $result_item->customer_number,
                                    'invoice_no' => $result_item->invoice_number,
                                    'billto_name' => substr($result_item->billto_name,0,30),
                                    'billto_address1' => substr($result_item->billto_address1,0,30),
                                    'billto_address2' => substr($result_item->billto_address2,0,30),
                                    'billto_zipcode' => substr($result_item->billto_zip,0,10),
                                    'billto_city' => substr($result_item->billto_city,0,20),
                                    'billto_state' => substr($result_item->billto_state,0,2),
                                    'shipto_code' => $result_item->shipto_code,
                                    'shipto_name' => substr($result_item->shipto_name,0,30),
                                    'shipto_address1' => substr($result_item->shipto_address1,0,30),
                                    'shipto_address2' => substr($result_item->shipto_address2,0,30),
                                    'shipto_zipcode' => substr($result_item->shipto_zip,0,10),
                                    'shipto_city' => substr($result_item->shipto_city,0,20),
                                    'shipto_state' => substr($result_item->shipto_state,0,2),
                                    'shipping_code' => $result_item->shipping_code,
                                    'terms_code' => $result_item->terms_code,
                                    'confirm_to' => $result_item->confirm_to,
                                    'email_address' => $result_item->email,
                                    'customer_po_no' => $result_item->po_number,
                                    'comment' => $result_item->comments,
                                    'taxable_amount' => $result_item->taxable_amount,
                                    'nontaxable_amount' => $result_item->nontaxable_amount,
                                    'sales_tax_amount' => $result_item->sales_tax_amount,
                                    'freight_amount' => $result_item->freight_amount,
                                    'discount_amount' => $result_item->discount_amount,
                                    'payment_type' => $result_item->payment_type,
                                    'check_number' => $result_item->check_number,
                                    'reference_number' => $result_item->reference_number,
                                    'deposit_amount' => $result_item->deposit_amount,
                                    'total' => $result_item->total,
                                    'net_order' => '',
                                    'web_sales_order_no' => $result_item->external_order_number,
                                    'web_user_id' => $result_item->user_id,
                                );

                                $this->storeSalesOrderLines($result_item->sales_order_number, $result_item->items);
                                $result[] = $t_data;

                                $t_result = $this->insert_db($table_name, $t_data, $t_keys );
                                if ($t_result) {
                                    $this->removeExportOrderQueue($result_item->external_order_number);
                                }
                            }
                        }

                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }


    public function getSalesOrdersHistory($ar_division_number = null, $customer_number = null, $sales_order_number = null, $page = 0, $limit = 20, $modified_from = '-1')
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'sales_orders';
        $endpoint = 'salesorders/history';
        $option_key = str_replace('/', '_', $endpoint);

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('ar_division_number' => $ar_division_number, 'customer_number' => $customer_number, 'sales_order_number' => $sales_order_number);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
        //Start / In Progress status

        $endpoint = '';
        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result = $response_data->data;

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                /*$result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );*/
            }
        }

        return $result;
    }

    function removeExportOrderQueue($web_sales_order_no)
    {
        global $wpdb;
        $table_prefix = $this->table_prefix;
        $table_name = $table_prefix . 'queue';
        $sql = "UPDATE $table_name SET `active` = 0 WHERE `web_sales_order_no` = '$web_sales_order_no'"; //i_print($sql);
        return $wpdb->query($sql);
    }

    function storeSalesOrderLines($sales_order_number, $order_lines, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $table_prefix = $this->table_prefix;
        $table_name = $table_prefix . 'sales_order_lines';
        $option_key = 'sales-order-lines';

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, 0, '-1');
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        if ($order_lines) {
            foreach ($order_lines as $line_data) {
                if (true) {

                    $t_data = array(
                        'sales_order_no' => $sales_order_number,
                        'item_code' => $line_data->item_code,
                        'item_type' => $line_data->item_type,
                        'item_code_desc' => $line_data->item_code_description,
                        'quantity' => $line_data->quantity,
                        'back_quantity' => $line_data->quantity_backordered,
                        'unit_price' => $line_data->price,
                        'line_discount_percent' => $line_data->discount_percentage,
                        'extension_amt' => $line_data->subtotal,
                        'comment' => $line_data->comment,
                    );

                    //insert_salesordersLines($result_item->items); //should be continue
                    $result[] = $t_data;

                    $t_result = $this->insert_db($table_name, $t_data, array('sales_order_no', 'item_code', 'quantity'));
                }
            }

            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '1',
                'run_result' => '1',
                'in_progress' => '0'
            );
            update_option('clink_' . $option_key . '_last_import', $last_import_data);
            //i_print($result);
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    /*
     *
     * Create Salesorders
     * customer_number - required
     * ar_division_number - required
     * order_date - required
     * user_id - required
     * external_order_number - required
     * items - required
     * shipto_code, shipto_address1, shipto_address2, shipto_city, shipto_state, shipto_zip, shipto_country,
     * shipping_code, confirm_to, po_number, comments, freight_amount, tax_schedule - null
     *
     * */
    function createSalesorders($data = array())
    {
        /*$result = array();*/

        /*if (!empty($data['customer_number']) && !empty($data['ar_division_number']) && !empty($data['order_date'])
                && !empty($data['user_id']) && !empty($data['external_order_number']) && !empty($data['items'])) {*/

        $result = $this->sendRequest('salesorders/create', $data, -1, -1, "POST");

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            //update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            /*$result = array();
            if ($createSalesorders->success) {
                $result = $createSalesorders->data;
            }*/
        }

        /*} else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "customer_number, ar_division_number, order_date, user_id, external_order_number and items are required!"];
        }*/

        return $result;
    }

    function getInvoices($history = true, $ar_division_number = null, $customer_number = null, $invoice_number = null, $header_sequence_number = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'invoices';
        $endpoint = 'invoices';
        $t_keys = array('invoice_no', 'header_seq_no');
        if ($history)
            $endpoint = 'invoices/history';

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, 0, '-1');
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $option_key = str_replace('/', '_', $endpoint);

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $result = array();
        $data = array();

        $data = array('ar_division_number' => $ar_division_number, 'customer_number' => $customer_number, 'invoice_number' => $invoice_number, 'header_sequence_number' => $header_sequence_number);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;

                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ( in_array($import_source, $this->store_when_source ) ) {
                            //$delete = $this->db_empty_table($table_name);
                            $result[] = $result_item;
                            $t_data = array(
                                'invoice_no' => $result_item->invoice_number,
                                'header_seq_no' => $result_item->header_sequence_number,
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            $t_data = array(
                                'invoice_no' => $result_item->invoice_number,
                                'header_seq_no' => $result_item->header_sequence_number,
                                'invoice_date' => $result_item->invoice_date,
                                'invoice_type' => $result_item->invoice_type,
                                'ar_division_no' => $result_item->ar_division_number,
                                'customer_no' => $result_item->customer_number,
                                'sales_order_no' => $result_item->sales_order_number,
                                'billto_name' => $result_item->billto_name,
                                'billto_address1' => $result_item->billto_address1,
                                'billto_address2' => $result_item->billto_address2,
                                'billto_zipcode' => $result_item->billto_zip,
                                'billto_city' => $result_item->billto_city,
                                'billto_state' => $result_item->billto_state,
                                'shipto_code' => $result_item->shipto_code,
                                'shipto_name' => $result_item->shipto_name,
                                'shipto_address1' => $result_item->shipto_address1,
                                'shipto_address2' => $result_item->shipto_address2,
                                'shipto_zipcode' => $result_item->shipto_zip,
                                'shipto_city' => $result_item->shipto_city,
                                'shipto_state' => $result_item->shipto_state,
                                'shipping_code' => $result_item->shipping_code,
                                'terms_code' => $result_item->terms_code,
                                'confirm_to' => $result_item->confirm_to,
                                'email_address' => $result_item->email,
                                'customer_po_no' => $result_item->po_number,
                                'comment' => $result_item->comments,
                                'taxable_amount' => $result_item->taxable_amount,
                                'nontaxable_amount' => $result_item->nontaxable_amount,
                                'sales_tax_amount' => $result_item->sales_tax_amount,
                                'freight_amount' => $result_item->freight_amount,
                                'discount_amount' => $result_item->discount_amount,
                                'payment_type' => $result_item->payment_type,
                                'check_number' => $result_item->check_number,
                                'reference_number' => $result_item->reference_number,
                                'deposit_amount' => $result_item->deposit_amount,
                                'total' => $result_item->total,
                                'net_invoice' => $result_item->total,
                                'balance' => $result_item->balance,
                                'payments_today' => $result_item->payments_today,
                            );

                            $this->storeInvoiceLines($result_item->invoice_number, $result_item->header_sequence_number, $result_item->items);
                            $result[] = $t_data;

                            $t_result = $this->insert_db($table_name, $t_data, $t_keys); //array('ar_division_no', 'customer_no', 'invoice_no')
                        }

                    }
                //i_print($result);

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function storeInvoiceLines($invoice_number, $header_sequence_number, $invoice_lines, $modified_from = '-1', $local = false)
    {

        global $wpdb;
        $table_prefix = $this->table_prefix;
        $table_name = $table_prefix . 'invoice_lines';
        $option_key = 'invoice-lines';

        $result = array();
        if ($local) {
            $result_data = $this->get_local_data($table_name, 0, '-1');
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        if ($invoice_lines) {
            foreach ($invoice_lines as $line_data) {
                if (true) {

                    $t_data = array(
                        'invoice_no' => $invoice_number,
                        'header_seq_no' => $header_sequence_number,
                        'line_key' => $line_data->line_key,
                        'item_code' => $line_data->item_code,
                        'item_type' => $line_data->item_type,
                        'item_code_desc' => $line_data->item_code_description,
                        'quantity' => $line_data->quantity,
                        'unit_price' => $line_data->price,
                        'line_discount_percent' => $line_data->discount_percentage,
                        'extension_amt' => $line_data->subtotal,
                        'comment' => $line_data->comment,
                    );

                    if (!empty($line_data->serials))
                        $this->store_invoice_serials($invoice_number, $header_sequence_number, $line_data->line_key, $line_data->serials);

                    $result[] = $t_data;

                    $t_result = $this->insert_db($table_name, $t_data, array('invoice_no', 'header_seq_no', 'line_key'));
                }
            }

            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '1',
                'run_result' => '1',
                'in_progress' => '0'
            );
            update_option('clink_' . $option_key . '_last_import', $last_import_data);
            //i_print($result);
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function store_invoice_serials($invoice_number, $header_sequence_number, $line_key, $serials, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $table_prefix = $this->table_prefix;
        $table_name = $table_prefix . 'invoice_serials';
        $option_key = 'invoice-serials';

        $result = array();
        if ($serials) {
            foreach ($serials as $serial_data) {
                if (isset($serial_data->lot_serial_number)) {

                    $t_data = array(
                        'invoice_no' => $invoice_number,
                        'header_seq_no' => $header_sequence_number,
                        'line_key' => $line_key,
                        'lot_serial_number' => $serial_data->lot_serial_number,
                        'quantity' => $serial_data->quantity,
                    );

                    $result[] = $t_data;

                    $t_result = $this->insert_db($table_name, $t_data, array('invoice_no', 'header_seq_no', 'line_key', 'lot_serial_number'));
                }
            }

            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '1',
                'run_result' => '1',
                'in_progress' => '0'
            );
            update_option('clink_' . $option_key . '_last_import', $last_import_data);
            //i_print($result);
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getInvoicesTracking($invoice_number = null, $header_sequence_number = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'invoice_trackings';
        $endpoint = 'invoices/trackings';
        $t_keys = array('invoice_no', 'header_seq_no', 'package_no');
        $option_key = str_replace('/', '_', $endpoint);

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('invoice_number' => $invoice_number, 'header_sequence_number' => $header_sequence_number);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $option_key . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ($result_item->invoice_number) {
                            if ( in_array($import_source, $this->store_when_source ) ) {
                                $result[] = $result_item;
                                $t_data = array(
                                    'invoice_no' => $result_item->invoice_number,
                                    'header_seq_no' => $result_item->header_sequence_number,
                                    'package_no' => $result_item->package_number
                                );
                                $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'invoice_no' => $result_item->invoice_number,
                                    'header_seq_no' => $result_item->header_sequence_number,
                                    'package_no' => $result_item->package_number,
                                    'tracking_id' => $result_item->tracking_number,
                                    'comment' => $result_item->comments,
                                );
                                $result[] = $t_data;
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                            }

                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getUsers($user_id = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false) //Next
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'users';
        $endpoint = 'users';
        $t_keys = array('user_id');
        //ar_division_no_for_existing_user

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }
        //Prepare Data to append if needed
        //$response_data = $this->getCustomers(null, null, 0, -1, true);

        /*if ($customer_number == null)
            $customer_number = CPLINK::get_module_option('customer_no_for_existing_user');
        if ($ar_division_number == null)
            $ar_division_number = CPLINK::get_module_option('ar_division_no_for_existing_user');*/

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('user_id' => $user_id);
        $response_data = $this->sendRequest('users', $data, -1, -1, "POST", $page, $limit, $modified_from);
        //i_print($customers); exit;

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ( in_array($import_source, $this->store_when_source ) ) {
                            //$delete = $this->db_empty_table($table_name);
                            $result[] = $result_item;
                            $t_data = array(
                                'user_id' => $result_item->user_id
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            $t_data = array(
                                'user_id' => $result_item->user_id,
                                'first_name' => $result_item->first_name,
                                'last_name' => $result_item->last_name,
                                'ar_division_no' => $result_item->ar_division_number,
                                'customer_no' => $result_item->customer_number,
                                'shipto_code' => $result_item->shipto_code,
                                'email_address' => $result_item->email,
                                'password' => $result_item->password,
                                'inactive_user' => $result_item->inactive_user,
                                'view_balance' => $result_item->view_balance,
                                'view_invoice' => $result_item->view_invoice
                            );
                            $result[] = $t_data;
                            $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    /*
    *
    * Create Users
    * user_id - required
    * ar_division_number - required
    * customer_number - required
    * first_name, last_name, shipto_code, email, password, inactive_user, view_balance, view_invoice - null
    *
    * */
    function createUsers($data = array())
    {
        $result = array();

        if (!empty($data['user_id']) && !empty($data['ar_division_number']) && !empty($data['customer_number'])) {
            $createUsers = $this->sendRequest('users/create', $data, -1, -1, "POST");

            if ($this->errorNo) {
                //i_print('ERROR: ' . $this->errorNo);
                //i_print($this->errorMessage);
            } else {
                $result = array();
                if ($createUsers->success) {
                    $result = $createUsers->data;
                }
            }

        } else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "ar_division_number, user_id and customer_number are required!"];
        }

        return $result;
    }

    /*
     *
     * Create Users
     * user_id - required
     * ar_division_number - required
     * customer_number - required
     * first_name, last_name, shipto_code, email, password, inactive_user, view_balance, view_invoice - null
     *
     * */
    function updateUsers($data = array())
    {
        $result = array();

        if (!empty($data['user_id']) && !empty($data['ar_division_number']) && !empty($data['customer_number'])) {
            $updateUsers = $this->sendRequest('users/update', $data, -1, -1, "POST");

            if ($this->errorNo) {
                //i_print('ERROR: ' . $this->errorNo);
                //i_print($this->errorMessage);
            } else {
                $result = array();
                if ($updateUsers->success) {
                    $result = $updateUsers->data;
                }
            }

        } else {
            $result = (object)array('data' => []);
            $result->data = (object)["success" => false, "message" => "ar_division_number, shipto_code and customer_number are required!"];
        }

        return $result;
    }

    function getPricecodes($page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'price_codes';
        $endpoint = 'pricecodes';
        $t_keys = array('price_code_record', 'price_code', 'item_code', 'customer_price_level', 'ar_division_no', 'customer_no');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $option_key = str_replace('/', '_', $endpoint);

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $result = array();
        $data = array();
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;

                if (count($result_data))
                    foreach ($result_data as $result_item) {

                        $result[] = $result_item;
                        if ( in_array($import_source, $this->store_when_source ) ) {
                            $t_data = array(
                                'price_code_record' => $result_item->price_code_record,
                                'price_code' => $result_item->price_code,
                                'item_code' => $result_item->item_code,
                                'customer_price_level' => $result_item->customer_price_level,
                                'ar_division_no' => $result_item->ar_division_number,
                                'customer_no' => $result_item->customer_number
                            );
                            $this->store_temp_data($table_name, $t_data, $t_keys);
                        }
                        if ($import_source != 'purge') {
                            $t_data = array(
                                'price_code_record' => $result_item->price_code_record,
                                'price_code' => $result_item->price_code,
                                'item_code' => $result_item->item_code,
                                'customer_price_level' => $result_item->customer_price_level,
                                'ar_division_no' => $result_item->ar_division_number,
                                'customer_no' => $result_item->customer_number,
                                'pricing_method' => $result_item->pricing_method,
                                'break_quantity1' => $result_item->break_quantity1,
                                'break_quantity2' => $result_item->break_quantity2,
                                'break_quantity3' => $result_item->break_quantity3,
                                'break_quantity4' => $result_item->break_quantity4,
                                'break_quantity5' => $result_item->break_quantity5,
                                'discount_markup1' => $result_item->discount_markup1,
                                'discount_markup2' => $result_item->discount_markup2,
                                'discount_markup3' => $result_item->discount_markup3,
                                'discount_markup4' => $result_item->discount_markup4,
                                'discount_markup5' => $result_item->discount_markup5,
                            );
                            $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                        }

                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        //Prepare PriceLevelsByCustomerPriceCodes
        //$this->getPriceLevelsByCustomerPriceCodes(null, $page, $limit, $modified_from);

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getWarehouses($warehouse_code = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {

        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'warehouses';
        $endpoint = 'warehouses';
        $t_keys = array('warehouse_code');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }
        /*if( $customer_number == null )
            $customer_number = CPLINK::get_module_option( 'customer_no_for_existing_user');*/

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('warehouse_code' => $warehouse_code);
        $warehouses = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        //i_print($warehouses); exit;

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($warehouses->success) {
                $result_data = $warehouses->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ($result_item->warehouse_code) {

                            if ( in_array($import_source, $this->store_when_source ) ) {
                                $result[] = $result_item;
                                $t_data = array(
                                    'warehouse_code' => $result_item->warehouse_code,
                                );
                                $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'warehouse_code' => $result_item->warehouse_code,
                                    'warehouse_name' => $result_item->warehouse_name,
                                    'warehouse_description' => $result_item->warehouse_description,
                                    'address' => $result_item->address,
                                );
                                $result[] = $t_data;
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                            }

                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $warehouses->message
                );
            }
        }
        //i_print($result);
        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getTaxSchedules($tax_schedule = null, $page = 0, $modified_from = '-1', $limit = 20)
    {
        global $wpdb;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'taxschedules';
        $endpoint = 'taxschedules';


        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('tax_schedule' => $tax_schedule);
        $tax_schedule = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($tax_schedule->success) {
                $result = $tax_schedule->data;
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getShippingMethods($shipping_code = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'shipping_methods';
        $endpoint = 'shippingmethods';
        $t_keys = array('shipping_code');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('shipping_code' => $shipping_code);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        //i_print($customers); exit;

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ($result_item->shipping_code) {

                            if ( in_array($import_source, $this->store_when_source ) ) {
                                $result[] = $result_item;
                                $t_data = array(
                                    'shipping_code' => $result_item->shipping_code,
                                );
                                $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'shipping_code' => $result_item->shipping_code,
                                    'shipping_code_description' => $result_item->shipping_code_description,
                                    'freight_calculation_method' => $result_item->freight_calculation_method
                                );
                                $result[] = $t_data;
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys );
                            }
                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getPaymentTypes($payment_type = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'payment_types';
        $endpoint = 'paymenttypes';
        $t_keys = array('payment_type');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('payment_type' => $payment_type);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from); //payment_types

        //i_print($customers); exit;

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ($result_item->payment_type) {

                             if ( in_array($import_source, $this->store_when_source ) ) {
                                $result[] = $result_item;
                                $t_data = array(
                                    'payment_type' => $result_item->payment_type,
                                );
                                $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'payment_type' => $result_item->payment_type,
                                    'payment_description' => $result_item->payment_description,
                                    'payment_method' => $result_item->payment_method
                                );
                                $result[] = $t_data;
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                            }

                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getTermsCodes($terms_code = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'terms_code';
        $endpoint = 'termscode';
        $t_keys = array('terms_code');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('terms_code' => $terms_code);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ($result_item->terms_code) {

                            if ( in_array($import_source, $this->store_when_source ) ) {
                                 $result[] = $result_item;
                                 $t_data = array(
                                     'terms_code' => $result_item->terms_code,
                                 );
                                 $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'terms_code' => $result_item->terms_code,
                                    'terms_code_description' => $result_item->terms_code_description
                                );
                                $result[] = $t_data;
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                            }

                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }

    function getPriceLevelsByCustomerPriceCodes($price_code = null, $page = 0, $limit = 20, $modified_from = '-1', $local = false)
    {
        global $wpdb;
        $import_source = $this->import_source;
        $table_prefix = $this->table_prefix;
        $table_name = $this->current_method_table =  $table_prefix . 'pricelevels_by_customer_price_codes';
        $endpoint = 'pricelevels/customerpricecodes';
        $t_keys = array('ar_division_no', 'customer_no', 'product_line', 'price_code', 'effective_date');

        if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
            $this->purge_keys = array();
            foreach ($t_keys as $t_key){
                $this->purge_keys[$t_key] = 'i_null';
            }
            $fake_data = $this->purge_keys;
            $this->store_temp_data($table_name, $fake_data, $t_keys);
        }

        $option_key = str_replace('/', '_', $endpoint);

        if ($local) {
            $result = array();
            $result_data = $this->get_local_data($table_name, $page, $limit);
            if (count($result_data)) {
                $result = $result_data;
            }
            return $result;
        }

        $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
        if( is_object($modified_from) )
            return $modified_from;

        $data = array('price_code' => $price_code);
        $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
        //i_print($pricelevels); exit;

        $result = array();

        if ($this->errorNo) {
            $last_import_data = array(
                'date' => $this->current_date,
                'status' => '2',
                'run_result' => '2',
                'in_progress' => '0'
            );
            update_option('clink_' . $endpoint . '_last_import', $last_import_data);
        } else {
            $result = array();
            if ($response_data->success) {
                $result_data = $response_data->data;


                if (count($result_data))
                    foreach ($result_data as $result_item) {
                        if ($result_item->price_level) {

                            if ( in_array($import_source, $this->store_when_source ) ) {
                                $result[] = $result_item;
                                $t_data = array(
                                    'ar_division_no' => $result_item->ar_division_number,
                                    'customer_no' => $result_item->customer_number,
                                    'product_line' => $result_item->product_line,
                                    'price_code' => $result_item->price_code,
                                    'effective_date' => $result_item->effective_date,
                                );
                                $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'ar_division_no' => $result_item->ar_division_number,
                                    'customer_no' => $result_item->customer_number,
                                    'product_line' => $result_item->product_line,
                                    'price_code' => $result_item->price_code,
                                    'effective_date' => $result_item->effective_date,
                                    'end_date' => $result_item->end_date,
                                    'price_level' => $result_item->price_level,
                                );
                                $result[] = $t_data;
                                $t_result = $this->insert_db($table_name, $t_data, $t_keys);
                            }

                        }
                    }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
                //i_print($result);
            } else {

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                $result = array(
                    'status' => 0,
                    'message' => $response_data->message
                );
            }
        }

        $this->last_import_data = $last_import_data;
        return $result;
    }


    // Import Methods

    public function insertProducts($sage_products = array())
    {
        /*
         * */
        if (!$this->isset_return($_GET, 'cp_action') && !current_user_can('administrator') && !$this->is_cron)
            return 'You have not permission';

        // i_print('Prepare fresh Product Lines to get category info');
        //$product_lines = $this->getProductLines(null, 0, -1, true);
        // i_print('Prepare fresh Inventories');
        //$inventories = $this->getInventories(null, null, 0, -1, true);
        // i_print('Prepare fresh Price Codes');
        //$price_codes = $this->getPricecodes(0, -1, true);
        // i_print('Prepare fresh Warehouses to get stock info');
        //$warehouses = $this->getWarehouses(null, 0, -1, true);

        // get default_warehouse option from settings
        //cplink-settings-modules
        $image_path = CPLINK::get_scopConfig('image_path');
        $products_default_status = CPLINK::get_module_option('products_default_status');
        $creation_fields = CPLINK::get_module_option('sync_field_on_creation');
        $updating_fields = CPLINK::get_module_option('products_sync_field_on_update');
        $products_default_weight = CPLINK::get_module_option('products_default_weight');
        $on_sage_internet_enabled_disable = CPLINK::get_module_option('on_sage_internet_enabled_disable');
        $enable_sage_inventory = CPLINK::get_module_option('enable_sage_inventory');
        $use_default_warehouse_inventory = CPLINK::get_module_option('use_default_warehouse_inventory');
        if ($use_default_warehouse_inventory) {
            $default_warehouse = CPLINK::get_module_option('default_warehouse');
        } else {
            $default_warehouse = '';
        }
        //i_print($creation_fields);

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $t_date = wp_date("Y-m-d h:i:s");
        $p_i = 1;

        $post_status = 'publish';
        if ($products_default_status === '0') {
            //$post_status = 'draft';
        }

        if (!empty($sage_products)) {
            foreach ($sage_products as $sage_product) {
                $sage_product = (array)$sage_product;

                $post_type = 'product';

                $item_code = $this->isset_return($sage_product, 'item_code');
                $post_title = $this->isset_return($sage_product, 'item_code_description');
                //$post_excerpt = $this->isset_return($sage_product, 'item_code_description');
                $product_line = $this->isset_return($sage_product, 'product_line');
                $image_file = $this->isset_return($sage_product, 'image_file');
                $ebm_enabled = $this->isset_return($sage_product, 'ebm_enabled');

                if (empty($post_title))
                    $post_title = $item_code;

                $woo_sage_map = array(
                    '_price' => 'standard_unit_price',// ? need to compare with regular and sale price to decide
                    '_regular_price' => 'standard_unit_price',
                    //'_cp_item_type' => 'item_type',
                    '_cp_item_code' => 'item_code',
                    '_sku' => 'item_code',
                    //'_cp_tax_class' => 'tax_class',
                    '_weight' => 'weight',
                    //'_cp_warranty_code' => 'warranty_code',
                    //'_cp_commission_rate' => 'commission_rate',
                    //'_cp_sale_method' => 'sale_method',
                );

                //Get Categories
                $product_categories = array();
                /*if ($product_line) {
                    if (isset($product_lines[$product_line])) {
                        array_push($product_categories, $product_lines[$product_line]);
                    }
                }*/

                //Get Attributes
                $product_attributes = array();
                $attr_i = 1;
                while (isset($sage_product['category' . $attr_i]) && !empty($sage_product['category' . $attr_i])) {
                    array_push($product_attributes, $sage_product['category' . $attr_i]);
                    $attr_i++;
                }

                if ($item_code) {
                    $product_data = array(
                        'post_title' => $post_title,
                        'post_name' => '',
                        'post_content' => '',
                        'post_excerpt' => '',
                        'post_status' => $post_status,
                        'post_type' => $post_type,
                    );
                    //i_print($product_data);

                    $args = array(
                        'meta_query' => array(
                            array(
                                'key' => '_sku',
                                'value' => $item_code
                            )
                        ),
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                    );
                    $posts_exist = get_posts($args); //Check if product exist by unical key
                    $post_id = '';
                    $insert_type = 'new';
                    if (empty($posts_exist)) {
                        //i_print('Product with $item_code='.$item_code.' not exist');
                        if (!$ebm_enabled)
                            continue;
                        $insert_fields = $creation_fields;

                        if (!empty($insert_fields) && !in_array('post_title', $insert_fields))
                            unset($product_data['post_title']);

                        $post_id = wp_insert_post($product_data);
                    } else {
                        $insert_type = 'update';
                        $insert_fields = $updating_fields;

                        if (!empty($insert_fields) && !in_array('post_title',$insert_fields))
                            unset($product_data['post_title']);

                        $post_id = $exist_post_id = $posts_exist[0];
                        $product_data['ID'] = $post_id;
                        unset($product_data['post_type']);
                        unset($product_data['post_status']);

                        if (!$ebm_enabled && $on_sage_internet_enabled_disable == 'disable_product') {
                            $product_data['post_status'] = 'draft';
                        }

                        wp_update_post($product_data);
                        //i_print('Product with $item_code='.$item_code.' already exist');
                    }

                    if ($post_id) {
                        update_post_meta($post_id, 'cp_sage_product', '1');
                        update_post_meta($post_id, 'cp_updated_date', $t_date); //i_print($post_id);

                        //Get Picture and attach to product
                        if (is_array($insert_fields) && in_array('picture', $insert_fields) && $image_path && $image_file) {
                            $image_url = $image_path . $image_file;

                            $path_parts = pathinfo($image_url);
                            $base_name = $path_parts['basename'];
                            $file_name = $path_parts['filename'];
                            $desc = $file_name;

                            $attachment_exist = CPLINK::get_attachment_by_post_name($file_name);

                            if (!$attachment_exist) {
                                $image_id = CPLINK::media_sideload_image($image_url, $post_id, $desc, 'id');
                                if (is_wp_error($image_id)) {
                                    $image_id = '';
                                    //$error_string = $image_id->get_error_message(); i_print($error_string);
                                }
                            } else {
                                $image_id = $attachment_exist->ID;
                            }

                            if ($image_id)
                                set_post_thumbnail($post_id, $image_id);

                        }

                        foreach ($woo_sage_map as $woo_field => $sage_field) {
                            $can_insert = true;
                            if (!empty($insert_fields)) {
                                switch ($woo_field) {
                                    case '_price':
                                    case '_regular_price':
                                        if (!in_array('_price',$insert_fields))
                                            $can_insert = false;
                                        break;
                                    case '_weight':
                                        if (!in_array($woo_field,$insert_fields))
                                            $can_insert = false;
                                        break;
                                    default:
                                        $can_insert = true;
                                        break;
                                }
                            }
                            if (!$can_insert)
                                continue;

                            $field_value = $this->isset_return($sage_product, $sage_field);
                            if ($sage_field == 'weight' && !$field_value && $products_default_weight) {
                                $field_value = $products_default_weight;
                            }

                            if ($field_value) {
                                //i_print($woo_field.' = '.$field_value);
                                update_post_meta($post_id, $woo_field, $field_value);
                            }
                        }

                        // Prepare(create) categories and append to product
                        if (count($product_categories)) {
                            foreach ($product_categories as $cat_data) {
                                $taxonomy = 'product_cat';
                                $append = true;

                                if (!isset($cat_data->product_line))
                                    continue;

                                $cat_name = $cat_data->product_line;
                                $cat_desc = $cat_data->product_line_desc;
                                //get the category to check if exists
                                $cat = get_term_by('name', $cat_name, $taxonomy);
                                $cat_id = 0;
                                if ($cat == false) { //category not exist -> create it
                                    $cat = wp_insert_term(
                                        $cat_name,
                                        $taxonomy,
                                        array(
                                            'description' => $cat_desc,
                                            //'slug'        => 'apple',
                                            //'parent'      => $parent_term_id,
                                        )
                                    );
                                    if (!is_wp_error($cat) && $cat) {
                                        $cat_id = $cat['term_id'];
                                    } else {
                                        /*echo $cat_name;
                                        $error_string = $cat->get_error_message();
                                        i_print($error_string);*/
                                    }
                                } else {
                                    $cat_id = $cat->term_id;
                                }
                                //i_print($cat);

                                //setting post category
                                if ($cat_id)
                                    $append_cat = wp_set_post_terms($post_id, array($cat_id), $taxonomy, $append);
                                //wp_set_object_terms($product_id, $product['product_cat'], 'product_cat');
                            }
                        }

                        //if ($enable_sage_inventory){
                        if ($insert_fields && in_array('_stock', $insert_fields)) {
                            // Prepare(create) stock from warehouse and append to product by using default_warehouse option from settings
                            $item_stock = CPLINK::get_sage_item_stock($item_code, $default_warehouse);
                            $item_stock = intval($item_stock);
                            $stock_status = 'instock';
                            $manage_stock = 'yes';
                            if ($item_stock < 1)
                                $stock_status = 'outofstock';

                            update_post_meta($post_id, '_stock', $item_stock);
                            update_post_meta($post_id, '_stock_status', $stock_status);
                            update_post_meta($post_id, '_manage_stock', $manage_stock);
                        }
                    }
                }
                $p_i++;
            }
        }

        return true;
        /*$post_id = wp_insert_post($product_data);
        wp_set_object_terms($post_id, 'simple', 'product_type');
        //wp_set_object_terms( $post_id, 'variable', 'product_type' );
        update_post_meta($post_id, '_price', '156');
        update_post_meta($post_id, '_featured', 'yes');
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
    }

    public function insertInventories($inventory_data = array())
    {
        if (!$this->isset_return($_GET, 'cp_action') && !current_user_can('administrator') && !$this->is_cron)
            return 'You have not permission';

        // get default_warehouse option from settings
        $default_warehouse = CPLINK::get_module_option('default_warehouse');

        if (is_array($inventory_data) && count($inventory_data)) {
            foreach ($inventory_data as $inventory_item) {
                $item_warehouse = $inventory_item->warehouse_code;
                /*if ($default_warehouse && $item_warehouse != $default_warehouse)
                    continue;*/

                $item_code = $inventory_item->item_code;
                $item_stock = CPLINK::get_sage_item_stock($item_code, $default_warehouse); //$inventory_item->quantity_available;

                $args = array(
                    'meta_query' => array(
                        array(
                            'key' => '_sku',
                            'value' => $item_code
                        )
                    ),
                    'post_type' => 'product',
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                );
                $posts_exist = get_posts($args); //Check if product exist by unical key

                if ($posts_exist) {
                    $post_id = $posts_exist[0];
                    if ($post_id) {
                        $item_stock = intval($item_stock);
                        $stock_status = 'instock';
                        if ($item_stock < 1)
                            $stock_status = 'outofstock';

                        update_post_meta($post_id, '_stock', $item_stock);
                        update_post_meta($post_id, '_stock_status', $stock_status);
                    }
                }
            }
        }
    }

    public function insertCustomers($sage_customers = array()) // No used
    {
        if (!$this->isset_return($_GET, 'cp_action') && !current_user_can('administrator') && !$this->is_cron)
            return 'You have not permission';
        return;
        //Prepare Data to append if needed
        $product_lines = $this->getCustomersAddresses(null, null, null, 0, -1, true); //i_print($product_lines);
        //$inventories = $this->getInventories(null, null, 0, -1);

        //return;
        //cplink-settings-modules
        $creation_fields = CPLINK::get_module_option('sync_field_on_creation');
        $updating_fields = CPLINK::get_module_option('sync_field_on_update');
        //i_print($creation_fields);


        $t_date = wp_date("Y-m-d h:i:s");
        $p_i = 1;
        if (!empty($sage_customers)) {
            foreach ($sage_customers as $sage_customer) {
                $sage_customer = (array)$sage_customer;

                $customer_name = $this->isset_return($sage_customer, 'customer_name');
                $user_email = $this->isset_return($sage_customer, 'email_address');

                $user_name = str_replace(' ', '', $customer_name);

                $woo_sage_map = array(
                    'cp_ar_division_no' => 'ar_division_no',// ? need to compare with regular and sale price to decide
                    'cp_customer_no' => 'customer_no',
                    'cp_shipto_code' => 'primary_shipto_code',
                    'billing_address_1' => 'address_line1',
                    'billing_address_2' => 'address_line2',
                    'billing_postcode' => 'zip_code',
                    'billing_country' => 'country_code',
                    'billing_city' => 'city',
                    'billing_state' => 'state',
                    'billing_phone' => 'telephone_no',
                    'cp_contact_name' => 'contact_name',
                    'cp_contact_telephone_no1' => 'contact_telephone_no1',
                    'cp_price_level' => 'price_level',
                    'cp_tax_schedule' => 'tax_schedule',
                    'cp_current_balance' => 'current_balance',
                    'cp_customer_discount_rate' => 'customer_discount_rate',
                    'cp_terms_code' => 'terms_code',
                    'cp_credit_hold' => 'credit_hold',
                    'cp_credit_limit' => 'credit_limit',
                );

                if ($user_email) {
                    //i_print($userdata);

                    $insert_type = 'new';

                    $user_id = email_exists($user_email);
                    if (!$user_id) {
                        //i_print('Product with $item_code='.$item_code.' not exist');
                        if (username_exists($user_name)) {
                            $user_name .= rand(1, 100);
                        }

                        $random_password = wp_generate_password(12, false);
                        $userdata = array(
                            'user_pass' => $random_password,   //(string) The plain-text user password.
                            'user_login' => $user_name,   //(string) The user's login username.
                            'user_email' => $user_email,   //(string) The user email address.
                            'first_name' => $customer_name,   //(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
                            'last_name' => '',   //(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
                            'role' => 'customer', //'subscriber',   //(string) User's role.
                            'show_admin_bar_front' => false
                        );

                        $user_id = wp_insert_user($userdata);
                    } else {
                        $insert_type = 'update';
                    }

                    if ($user_id) {
                        update_user_meta($user_id, 'cp_sage_customer', '1');
                        update_user_meta($user_id, 'cp_updated_date', $t_date);

                        if ($insert_type == 'new') {
                            $insert_fields = $creation_fields;
                        } else {
                            $insert_fields = $updating_fields;
                        }

                        foreach ($woo_sage_map as $wp_field => $sage_field) {
                            $field_value = $this->isset_return($sage_customer, $sage_field);
                            if ($field_value) {
                                //i_print($wp_field.' = '.$field_value);
                                update_user_meta($user_id, $wp_field, $field_value);
                            }
                        }

                    }
                }
                $p_i++;
            }
        }

        return true;
    }

    public function insertUsers($sage_users = array())
    {
        if (!$this->isset_return($_GET, 'cp_action') && !current_user_can('administrator') && !$this->is_cron)
            return 'You have not permission';

        global $cp_modules_cf;
        //return;
        //cplink-settings-modules
        $creation_fields = CPLINK::get_module_option('sync_field_on_creation');
        $updating_fields = CPLINK::get_module_option('sync_field_on_update');
        //i_print($creation_fields);
        $send_email_on_customer_create = CPLINK::get_module_option('send_email_on_customer_create');
        if ($send_email_on_customer_create != '1')
            remove_action('register_new_user', 'wp_send_new_user_notifications');


        $t_date = wp_date("Y-m-d h:i:s");
        $p_i = 1;
        if (!empty($sage_users)) {
            foreach ($sage_users as $sage_user) {
                $sage_user = (array)$sage_user;

                $first_name = $this->isset_return($sage_user, 'first_name');
                $last_name = $this->isset_return($sage_user, 'last_name');
                $user_name = $first_name . ' ' . $last_name;
                $user_email = $this->isset_return($sage_user, 'email_address');
                $user_password = $this->isset_return($sage_user, 'password');;
                $shipto_code = $this->isset_return($sage_user, 'shipto_code');;

                $user_name = str_replace(' ', '', $user_name);

                $woo_sage_map = array(
                    'cp_ar_division_no' => 'ar_division_no',// ? need to compare with regular and sale price to decide
                    'cp_customer_no' => 'customer_no',
                    /*'cp_shipto_code' => 'shipto_code',*/
                    'cp_inactive_user' => 'inactive_user',
                    'cp_view_balance' => 'view_balance',
                    'cp_view_invoice' => 'view_invoice',
                    'cp_web_user_id' => 'user_id',

                );

                if ($user_email) {
                    //i_print($userdata);

                    $insert_type = 'new';

                    $user_id = email_exists($user_email);
                    if (!$user_id) {
                        //i_print('Product with $item_code='.$item_code.' not exist');
                        if (username_exists($user_name)) {
                            $user_name .= rand(1, 100);
                        }

                        $random_password = (!empty($user_password)) ? $user_password : wp_generate_password(12, false);

                        $userdata = array(
                            'user_pass' => $random_password,   //(string) The plain-text user password.
                            'user_login' => $user_name,   //(string) The user's login username.
                            'user_email' => $user_email,   //(string) The user email address.
                            'first_name' => $first_name,   //(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
                            'last_name' => $last_name,   //(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
                            'role' => 'customer', //'subscriber',   //(string) User's role.
                            'show_admin_bar_front' => false
                        );

                        $user_id = wp_insert_user($userdata);
                    } else {
                        $insert_type = 'update';
                        $customers_sync_field_on_update = $this->isset_return($cp_modules_cf, 'customers_sync_field_on_update');

                        $fields_to_update = array();
                        if (!empty($customers_sync_field_on_update)) {
                            /*$random_password = (!empty($user_password)) ? $user_password : wp_generate_password(12, false);*/
                            foreach ($customers_sync_field_on_update as $user_key => $user_value) {
                                switch ($user_value) {
                                    case 'first_name':
                                        $fields_to_update['first_name'] = $first_name;
                                        break;
                                    case 'last_name':
                                        $fields_to_update['last_name'] = $last_name;
                                        break;
                                    case 'email':
                                        $fields_to_update['user_email'] = $user_email;
                                        break;
                                    case 'password':
                                        (!empty($user_password) ? $fields_to_update['user_pass'] = $user_password : '');
                                        break;
                                }
                            }
                            if (!empty($fields_to_update)) {
                                $fields_to_update['ID'] = $user_id;
                                wp_update_user($fields_to_update);
                            }
                        }
                    }

                    if ($user_id) {
                        update_user_meta($user_id, 'cp_sage_user', '1');
                        update_user_meta($user_id, 'cp_updated_date', $t_date);

                        if ($insert_type == 'new') {
                            $insert_fields = $creation_fields;
                        } else {
                            $insert_fields = $updating_fields;
                        }

                        foreach ($woo_sage_map as $wp_field => $sage_field) {
                            $field_value = $this->isset_return($sage_user, $sage_field);
                            if ($field_value) {
                                //i_print($wp_field.' = '.$field_value);
                                update_user_meta($user_id, $wp_field, $field_value);
                            }
                        }

                        $cp_customer_no = $this->isset_return($sage_user, 'customer_no');
                        $cp_ar_division_no = $this->isset_return($sage_user, 'ar_division_no');

                        CPLINK::woocommerce_registration_custom_fields_save($user_id, $cp_customer_no, $cp_ar_division_no, false, true, $first_name, $last_name, '', $shipto_code);

                    }
                }
                $p_i++;
            }
        }

        return true;
    }
}