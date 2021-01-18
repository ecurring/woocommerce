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

    public function get_customer_id(): int
    {
        return 0;
    }

    public function get_billing_first_name(): string
    {
        return '';
    }

    public function get_billing_last_name(): string
    {
        return '';
    }

    public function get_billing_email(): string
    {
        return '';
    }

    public function get_date_created(): ?DateTime
    {
        return new DateTime();
    }
}

class WC_Order_Item
{

}

class WC_Order_Item_Product extends WC_Order_Item
{
    public function get_product(): WC_Product
    {
        return new WC_Product();
    }
}

class WC_Product
{
    public function get_meta(string $metaKey, bool $single = true)
    {
    }
}

class WC_Order_Refund extends WC_Order
{
}

class WC_Payment_Gateway
{
    public function supports(string $feature): bool
    {
        return false;
    }
}
