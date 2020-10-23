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
        if (get_transient('ecurring_all_subscription_plans')) {
            return get_transient('ecurring_all_subscription_plans');
        }

        $subscriptionPlans = json_decode(
            $this->api->apiCall('GET', 'https://api.ecurring.com/subscription-plans')
        );

        set_transient('ecurring_all_subscription_plans', $subscriptionPlans, 5 * MINUTE_IN_SECONDS);
    }
}
