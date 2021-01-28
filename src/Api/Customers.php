<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Api;

use eCurring_WC_Helper_Api;

class Customers
{
    /**
     * @var ApiClientInterface
     */
    protected $apiClient;
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    /**
     * Customers constructor.
     *
     * @param eCurring_WC_Helper_Api $api Old API client, still used by some methods.
     * @param ApiClientInterface $apiClient A new API client.
     */
    public function __construct(eCurring_WC_Helper_Api $api, ApiClientInterface $apiClient)
    {
        $this->api = $api;
        $this->apiClient = $apiClient;
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

    /**
     * Create an eCurring customer with given attributes.
     *
     * @param array $customerAttributes Attributes to use for create customer API call.
     *
     * @throws ApiClientException If request failed.
     *
     * @return array Created customer data.
     */
    public function createCustomer(array $customerAttributes): array
    {
        $requestData = [
            'data' => [
                'type' => 'customer',
                'attributes' => $customerAttributes,
            ],
        ];

        return $this->apiClient->apiCall(
            'POST',
            'https://api.ecurring.com/customers?_beta=1',
            $requestData
        );
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
