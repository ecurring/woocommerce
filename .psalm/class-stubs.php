<?php

class WC_Order
{
    public function get_id():int
    {
        return 0;
    }

    public function get_items(): array
    {
        return [
            new WC_Order_Item_Product(),
        ];
    }

    public function save():void
    {
    }

    public function get_meta(string $key, bool $single = true)
    {
    }

    public function update_meta_data(string $key, $data):void
    {
    }

    public function add_order_note(string $note): void
    {
    }
}

class WC_Order_Item_Product
{
    public function get_product(): WC_Product
    {
        return new WC_Product();
    }
}

class WC_Product
{
    public function get_meta(string $metaKey, bool $single = true);
}

class WC_Order_Refund extends WC_Order
{
}

class WC_Payment_Gateway
{
    public function supports(string $feature): bool
    {
    }
}