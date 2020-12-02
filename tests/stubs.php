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
	public function get_id()
	{
	}

    public function get_customer_id()
    {
        return 1;
    }

    public function get_items()
    {
    	return [];
    }

    public function get_meta()
    {
    }

    public function update_meta_data()
    {
    }

    public function add_order_note()
    {
    }

    public function save()
    {
    }
}

if(! class_exists(WC_Order_Item::class))
{
	class WC_Order_Item
	{
	}
}

if(! class_exists(WC_Order_Item_Product::class))
{
	class WC_Order_Item_Product
	{
		public function get_product()
		{
		}
	}
}

if(! class_exists(WC_Product::class))
{
	class WC_Product
	{
		public function get_meta()
		{
		}

		public function meta_exists()
		{
		}
	}
}
