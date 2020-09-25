<?php

namespace eCurring\WooEcurring;

use eCurring_WC_Helper_Api;

class SubscriptionActions
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $apiHelper;

    /**
     * @var string
     */
    private $subscriptionId;

    /**
     * @param eCurring_WC_Helper_Api $apiHelper
     * @param string $subscriptionId
     */
    public function __construct(eCurring_WC_Helper_Api $apiHelper, $subscriptionId)
    {
        $this->apiHelper = $apiHelper;
        $this->subscriptionId = $subscriptionId;
    }

    public function cancel()
    {
        $this->apiHelper->apiCall(
            'PATCH',
            "https://api.ecurring.com/subscriptions/{$this->subscriptionId}",
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $this->subscriptionId,
                    'attributes' => [
                        'status' => 'cancelled',
                    ],
                ],
            ]
        );
    }

    public function pause()
    {
        $this->apiHelper->apiCall(
            'PATCH',
            "https://api.ecurring.com/subscriptions/{$this->subscriptionId}",
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $this->subscriptionId,
                    'attributes' => [
                        'status' => 'paused',
                        //'resume_date' => '',
                    ],
                ],
            ]
        );
    }

    public function resume()
    {
        $this->apiHelper->apiCall(
            'PATCH',
            "https://api.ecurring.com/subscriptions/{$this->subscriptionId}",
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $this->subscriptionId,
                    'attributes' => [
                        'status' => 'active',
                    ],
                ],
            ]
        );
    }

    /**
    “switch subscription” means the current subscription is cancelled with chosen ‘switch date’ and new subscription has start date on ‘switch date’.
    The mandate of the current subscription can be copied to the new subscription.
     */
    public function change()
    {
    }
}
