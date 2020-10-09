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
        return $this->apiHelper->apiCall(
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

    public function create($data)
    {
        return $this->apiHelper->apiCall(
            'POST',
            'https://api.ecurring.com/subscriptions',
            $data
        );
    }

    public function import($page)
    {
        return $this->apiHelper->apiCall(
            'GET',
            "https://api.ecurring.com/subscriptions?page[number]={$page}"
        );
    }
}
