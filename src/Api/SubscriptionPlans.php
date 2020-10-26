<?php

namespace Ecurring\WooEcurring\Api;

use eCurring_WC_Helper_Api;

class SubscriptionPlans
{
    /** @var eCurring_WC_Helper_Api */
    private $api;

    public function __construct(eCurring_WC_Helper_Api $api)
    {
        $this->api = $api;
    }

    public function getSubscriptionPlans()
    {
        return json_decode(
            $this->api->apiCall('GET', 'https://api.ecurring.com/subscription-plans')
        );
    }
}
