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
}

class WC_Order_Refund extends WC_Order
{
}
