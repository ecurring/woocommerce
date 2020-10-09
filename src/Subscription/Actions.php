<?php

namespace eCurring\WooEcurring\Subscription;

use eCurring_WC_Helper_Api;

class Actions
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $apiHelper;

    /**
     * @param eCurring_WC_Helper_Api $apiHelper
     */
    public function __construct(eCurring_WC_Helper_Api $apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    public function cancel($subscriptionId)
    {
        return $this->apiHelper->apiCall(
            'PATCH',
            "https://api.ecurring.com/subscriptions/{$subscriptionId}",
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscriptionId,
                    'attributes' => [
                        'status' => 'cancelled',
                    ],
                ],
            ]
        );
    }

    public function pause($subscriptionId, $resumeDate)
    {
        return $this->apiHelper->apiCall(
            'PATCH',
            "https://api.ecurring.com/subscriptions/{$subscriptionId}",
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscriptionId,
                    'attributes' => [
                        'status' => 'paused',
                        'resume_date' => $resumeDate,
                    ],
                ],
            ]
        );
    }

    public function resume($subscriptionId)
    {
        $this->apiHelper->apiCall(
            'PATCH',
            "https://api.ecurring.com/subscriptions/{$subscriptionId}",
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscriptionId,
                    'attributes' => [
                        'status' => 'active',
                    ],
                ],
            ]
        );
    }

    /**
     * “switch subscription” means the current subscription is cancelled with chosen ‘switch date’ and new subscription has start date on ‘switch date’.
     * The mandate of the current subscription can be copied to the new subscription.
     */
    public function change()
    {
    }

    public function import($page)
    {
        return $this->apiHelper->apiCall(
            'GET',
            "https://api.ecurring.com/subscriptions?page[number]={$page}"
        );
    }
}
