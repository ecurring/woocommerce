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
        $subscriptionPlans = get_transient('ecurring_subscription_plans');
        if($subscriptionPlans !== false) {
            return $subscriptionPlans;
        }

        $response = json_decode(
            $this->api->apiCall('GET', 'https://api.ecurring.com/subscription-plans')
        );

        set_transient('ecurring_subscription_plans', $response, 5 * MINUTE_IN_SECONDS);
        return $response;
    }
}
