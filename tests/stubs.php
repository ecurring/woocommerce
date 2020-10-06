<?php

if (!defined('WC_VERSION')) {
    define('WC_VERSION', '4.0');
}

class WC_Payment_Gateway
{
	public function supports()
	{
	}
}

class WC_Order
{
    public function get_customer_id()
    {
        return 1;
    }
}

if (!function_exists('add_filter')) {
    function add_filter()
    {
    }
}
