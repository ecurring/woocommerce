<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Api;

use DateTime;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactory;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;
use eCurring_WC_Helper_Api;

class Subscriptions
{
    /**
     * @var ApiClient
     */
    protected $apiClient;
    /**
     * @var DataBasedSubscriptionFactory
     */
    protected $subscriptionFactory;
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $apiHelper;

    /**
     * @param eCurring_WC_Helper_Api $apiHelper
     * @param ApiClient $apiClient
     * @param DataBasedSubscriptionFactory $subscriptionFactory
     */
    public function __construct(eCurring_WC_Helper_Api $apiHelper, ApiClient $apiClient, DataBasedSubscriptionFactory $subscriptionFactory)
    {
        $this->apiHelper = $apiHelper;
        $this->apiClient = $apiClient;
        $this->subscriptionFactory = $subscriptionFactory;
    }

    /**
     * Send request to the eCurring API to activate the subscription.
     *
     * @param string $subscriptionId
     * @param DateTime $mandateAcceptedDate
     *
     * @throws ApiClientException
     */
    public function activate(string $subscriptionId, DateTime $mandateAcceptedDate): void
    {
        $requestData = [
            'data' => [
                'type' => 'subscription',
                'id' => $subscriptionId,
                'attributes' => [
                    'status' => 'active',
                    'mandate_accepted' => true,
                    'mandate_accepted_date' => $mandateAcceptedDate->format('c'),
                ],
            ],
        ];

        $this->apiClient->apiCall(
            'PATCH',
            sprintf('https://api.ecurring.com/subscriptions/%1$s', $subscriptionId),
            $requestData
        );
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
     * @param DateTime|null $resumeDate
     */
    public function pause($subscriptionId, $resumeDate = null)
    {
        $subscriptionData = [
            'data' => [
                'type' => 'subscription',
                'id' => $subscriptionId,
                'attributes' => [
                    'status' => 'paused',
                ],
            ],
        ];

        if ($resumeDate) {
            $subscriptionData['data']['attributes']['resume_date'] = $resumeDate->format('Y-m-d\TH:i:sP');
        }

        return json_decode(
            $this->apiHelper->apiCall(
                'PATCH',
                "https://api.ecurring.com/subscriptions/{$subscriptionId}",
                $subscriptionData
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

    /**
     * @param $subscriptionId
     *
     * @return SubscriptionInterface
     *
     * @throws ApiClientException
     * @throws SubscriptionFactoryException
     */
    public function getSubscriptionById(string $subscriptionId): SubscriptionInterface
    {
        $response = $this->apiClient->getSubscriptionById($subscriptionId);

        return $this->subscriptionFactory->createSubscription($response['data']);
    }
}
