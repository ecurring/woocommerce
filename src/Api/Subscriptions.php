<?php

namespace Ecurring\WooEcurring\Api;

use eCurring_WC_Helper_Api;

class Subscriptions
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

    /**
     * @param false|string $cancelDate
     */
    public function cancel($subscriptionId, $cancelDate)
    {
        $attributes = ['status' => 'cancelled'];
        if ($cancelDate) {
            $attributes = ['cancel_date' => $cancelDate];
        }

        return $this->apiHelper->apiCall(
            'PATCH',
            "https://api.ecurring.com/subscriptions/{$subscriptionId}",
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscriptionId,
                    'attributes' => $attributes,
                ],
            ]
        );
    }

    /**
     * @param false|string $resumeDate
     */
    public function pause($subscriptionId, $resumeDate)
    {
        return json_decode(
            $this->apiHelper->apiCall(
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
            )
        );
    }

    public function resume($subscriptionId)
    {
        return json_decode(
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
            )
        );
    }

    public function create(array $data)
    {
        return json_decode(
            $this->apiHelper->apiCall(
                'POST',
                'https://api.ecurring.com/subscriptions',
                $data
            )
        );
    }

    public function getSubscriptions(int $page)
    {
        return json_decode(
            $this->apiHelper->apiCall(
                'GET',
                "https://api.ecurring.com/subscriptions?page[number]={$page}"
            )
        );
    }

    public function getSubscriptionById($subscriptionId)
    {
        return json_decode(
            $this->apiHelper->apiCall(
                'GET',
                "https://api.ecurring.com/subscriptions/{$subscriptionId}"
            )
        );
    }
}
