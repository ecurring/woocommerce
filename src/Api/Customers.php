<?php

namespace Ecurring\WooEcurring\Api;

use eCurring_WC_Helper_Api;

class Customers
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    public function __construct(eCurring_WC_Helper_Api $api)
    {
        $this->api = $api;
    }

    public function getCustomerById($customerId)
    {
        return json_decode(
            $this->api->apiCall('GET', "https://api.ecurring.com/customers/{$customerId}")
        );
    }

    public function getCustomerSubscriptions($customerId)
    {
        return json_decode(
            $this->api->apiCall(
                'GET',
                "https://api.ecurring.com/customers/{$customerId}/subscriptions"
            )
        );
    }
}
