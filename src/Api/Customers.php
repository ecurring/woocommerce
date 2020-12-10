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
        $customer = get_transient("ecurring_customer_{$customerId}");
        if ($customer !== false) {
            return $customer;
        }

        $response = json_decode(
            $this->api->apiCall('GET', "https://api.ecurring.com/customers/{$customerId}")
        );

        set_transient("ecurring_customer_{$customerId}", $response, 5 * MINUTE_IN_SECONDS);
        return $response;
    }

    public function getCustomerSubscriptions($customerId)
    {
        $subscriptions = get_transient("ecurring_customer_subscriptions_{$customerId}");
        if ($subscriptions !== false) {
            return $subscriptions;
        }

        $response = json_decode(
            $this->api->apiCall(
                'GET',
                "https://api.ecurring.com/customers/{$customerId}/subscriptions"
            )
        );

        set_transient("ecurring_customer_subscriptions_{$customerId}", $response, 5 * MINUTE_IN_SECONDS);
        return $response;
    }
}
