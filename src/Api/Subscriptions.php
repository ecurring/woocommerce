<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Api;

use DateTime;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactory;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;
use eCurring_WC_Helper_Api;
use Exception;

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
     * @param ApiClientInterface $apiClient
     * @param DataBasedSubscriptionFactoryInterface $subscriptionFactory
     */
    public function __construct(
        eCurring_WC_Helper_Api $apiHelper,
        ApiClientInterface $apiClient,
        DataBasedSubscriptionFactoryInterface $subscriptionFactory
    ) {
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

    /**
     * @param string $ecurringCustomerId
     * @param string $subscriptionPlanId
     * @param array $attributes
     *
     * @return SubscriptionInterface
     *
     * @throws ApiClientException
     * @throws SubscriptionFactoryException
     */
    public function create(
        string $ecurringCustomerId,
        string $subscriptionPlanId,
        array $attributes = []
    ): SubscriptionInterface {

        $attributes['customer_id'] = $ecurringCustomerId;
        $attributes['subscription_plan_id'] = $subscriptionPlanId;
        $attributes['subscription_webhook_url'] = $this->getSubscriptionWebhookUrl();
        $attributes['transaction_webhook_url'] = $this->getTransactionWebhookUrl();

        $requestData = [
            'data' => [
                'type' => 'subscription',
                'attributes' => $attributes,
            ],
        ];

        $response = $this->apiClient->apiCall(
            'POST',
            'https://api.ecurring.com/subscriptions',
            $requestData
        );

        if (!isset($response['data'])) {
            throw new ApiClientException(
                sprintf(
                    'Failed to create subscription.' .
                    'No required \'data\' section was found in the response. ' .
                    'Response content: %1$s',
                    print_r($response, true)
                )
            );
        }

        $normalizedSubscriptionData = $this->normalizeSubscriptionData($response['data']);

        return $this->subscriptionFactory->createSubscription($normalizedSubscriptionData);
    }

    /**
     * @param int $page
     *
     * @return SubscriptionInterface[]
     * @throws ApiClientException
     * @throws SubscriptionFactoryException
     */
    public function getSubscriptions(int $page)
    {
        $response = $this->apiClient
            ->apiCall(
                'GET',
                "https://api.ecurring.com/subscriptions?page[number]={$page}"
            );

        if (!isset($response['data'])) {
            throw new ApiClientException(
                sprintf(
                    'Failed to get subscriptions list.' .
                    'No required \'data\' section was found in the response. ' .
                    'Response content: %1$s',
                    print_r($response, true)
                )
            );
        }

        $subscriptions = [];

        foreach ($response['data'] as $subscriptionData) {
            $normalizedSubscriptionData = $this->normalizeSubscriptionData($subscriptionData);
            $subscriptions[] = $this->subscriptionFactory->createSubscription($normalizedSubscriptionData);
        }

        return $subscriptions;
    }

    /**
     * Check if subscription exists on the eCurring side.
     *
     * @param string $subscriptionId Subscription id to check.
     *
     * @return bool
     * @throws ApiClientException
     */
    public function subscriptionExists(string $subscriptionId): bool
    {
        $url = sprintf(
            'https://api.ecurring.com/subscriptions/%1$s',
            $subscriptionId
        );

        $url = add_query_arg('fields[subscription]', 'id', $url);

        $subscriptionData = $this->apiClient->apiCall('GET', $url);

        return isset($subscriptionData['data']['id']);
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
        $normalizedSubscriptionData = $this->normalizeSubscriptionData($response['data']);

        return $this->subscriptionFactory->createSubscription($normalizedSubscriptionData);
    }

    /**
     * Get the url that should be used by eCurring for subscription webhooks.
     *
     * @return string
     */
    protected function getSubscriptionWebhookUrl(): string
    {
        return add_query_arg(
            'ecurring-webhook',
            'subscription',
            home_url('/')
        );
    }

    /**
     * Get the url that should be used by eCurring for transaction webhooks.
     *
     * @return string
     */
    protected function getTransactionWebhookUrl(): string
    {
        return add_query_arg(
            'ecurring-webhook',
            'transaction',
            home_url('/')
        );
    }

    /**
     * @param array $subscriptionData Subscription data that need to be normalized.
     *
     * @return array
     * @throws SubscriptionFactoryException
     */
    protected function normalizeSubscriptionData(array $subscriptionData): array
    {
        $subscriptionAttributes = (array) $subscriptionData['attributes'];

        return [
            'subscription_id' => $subscriptionData['id'],
            'customer_id' => $subscriptionData['relationships']['customer']['data']['id'],
            'subscription_plan_id' => $subscriptionData['relationships']['subscription-plan']['data']['id'],
            'mandate_code' => $subscriptionAttributes['mandate_code'] ?? '',
            'confirmation_page' => $subscriptionAttributes['confirmation_page'] ?? '',
            'confirmation_sent' => $subscriptionAttributes['confirmation_sent'] ?? false,
            'mandate_accepted' => $subscriptionAttributes['mandate_accepted'] ?? false,
            'mandate_accepted_date' => $this->createDateFromArrayField(
                $subscriptionAttributes,
                'mandate_accepted_date'
            ),
            'status' => $subscriptionAttributes['status'] ?? '',
            'start_date' => $this->createDateFromArrayField($subscriptionAttributes, 'start_date'),
            'cancel_date' => $this->createDateFromArrayField($subscriptionAttributes, 'cancel_date'),
            'resume_date' => $this->createDateFromArrayField($subscriptionAttributes, 'resume_date'),
            'created_at' => $this->createDateFromArrayField($subscriptionAttributes, 'created_at'),
            'updated_at' => $this->createDateFromArrayField($subscriptionAttributes, 'updated_at'),
            'archived' => $subscriptionAttributes['archived'] ?? false,
        ];
    }

    /**
     * @param array $subscriptionDataArray
     * @param string $dateFieldName
     *
     * @return DateTime|null Created object or null if field not set or equals null.
     *
     * @throws SubscriptionFactoryException If cannot create date from array field.
     */
    protected function createDateFromArrayField(array $subscriptionDataArray, string $dateFieldName): ?DateTime
    {
        try {
            $date = $subscriptionDataArray[$dateFieldName] ?
                new DateTime($subscriptionDataArray[$dateFieldName]) :
                null;
        } catch (Exception $exception) {
            throw new SubscriptionFactoryException(
                sprintf(
                    'Couldn\'t parse date in subscription data. Exception caught when trying to create a DateTime object: %1$s',
                    $exception->getMessage()
                )
            );
        }

        return $date;
    }
}
