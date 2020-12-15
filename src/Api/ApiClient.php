<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Api;

use eCurring_WC_Plugin;

class ApiClient implements ApiClientInterface
{

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @param string $apiKey Key required for authentication.
     */
    public function __construct(string $apiKey)
    {

        $this->apiKey = $apiKey;
    }

    /**
     * @inheritDoc
     */
    public function createSubscription(
        string $ecurringCustomerId,
        string $subscriptionPlanId,
        string $transactionWebhookUrl = ''
    ): array {

        $requestData = [
            'data' => [
                'type' => 'subscription',
                'attributes' => [
                    'customer_id' => $ecurringCustomerId,
                    'subscription_plan_id' => $subscriptionPlanId,
                    'transaction_webhook_url' => $transactionWebhookUrl,
                    'confirmation_sent' => true,
                    'metadata' => ['source' => 'woocommerce'],
                ],
            ],
        ];

        return $this->apiCall(
            'POST',
            'https://api.ecurring.com/subscriptions',
            $requestData
        );
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionById(string $subscription_id): array
    {
        $url = 'https://api.ecurring.com/subscriptions/' . $subscription_id;

        return $this->apiCall('GET', $url);
    }

    /**
     * @inheritdoc
     */
    public function activateSubscription(string $subscriptionId, string $mandateAcceptedDate): array
    {

        $requestData = [
            'data' => [
                'type' => 'subscription',
                'id' => $subscriptionId,
                'attributes' => [
                    'status' => 'active',
                    'mandate_accepted' => true,
                    'mandate_accepted_date' => $mandateAcceptedDate,
                ],
            ],
        ];

        return $this->apiCall(
            'PATCH',
            sprintf('https://api.ecurring.com/subscriptions/%1$s', $subscriptionId),
            $requestData
        );
    }

    /**
     * @inheritDoc
     */
    public function createCustomer(array $customerAttributes): array
    {
        $requestData = [
            'data' => [
                'type' => 'customer',
                'attributes' => $customerAttributes,
            ],
        ];

        return $this->apiCall(
            'POST',
            'https://api.ecurring.com/customers?_beta=1',
            $requestData
        );
    }

    /**
     * @inheritDoc
     */
    public function addMollieMandateToTheEcurringCustomer(string $customerId, string $mollieMandateId): array
    {
        $requestData = [
            'data' => [
                'type' => 'mandate',
                'attributes' => [
                    'customer_id' => $customerId,
                    'external_id' => $mollieMandateId,
                ],
            ],
        ];

        return $this->apiCall(
            'POST',
            'https://api.ecurring.com/mandates?_beta=1',
            $requestData
        );
    }

    /**
     * Make eCurring API request call.
     *
     * @param string     $method HTTP Method, one of the GET, POST, PATH, DELETE.
     * @param string     $url    Request target URL.
     * @param bool|array $data   Content to be sent in JSON-encoded format as request body.
     *
     * @return array Parsed response data.
     *
     * @throws ApiClientException
     */
    public function apiCall($method, $url, $data = false): array
    {

        $rawResponse = $this->doApiRequest($method, $url, $data);

        if (is_wp_error($rawResponse)) {
            throw new ApiClientException(
                sprintf(
                    'WP_Error returned for the API request: %1$s',
                    $rawResponse->get_error_message()
                ),
                $rawResponse->get_error_code()
            );
        }

        return $this->parseResponse($rawResponse['body']);
    }

    /**
     * Make API request and return raw result.
     *
     * @param string $method
     * @param string $url
     * @param array|bool $data
     *
     * @return mixed
     */
    protected function doApiRequest($method, $url, $data = false)
    {

        $args =  [
            'method' => $method,
            'headers' =>  [
                'X-Authorization' => $this->apiKey,
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ],
            'body' => $data ? json_encode($data) : '',
        ];

        return wp_remote_request($url, $args);
    }

    /**
     * Parse raw response body into an array
     *
     * @param string $rawResponseBody
     *
     * @return array
     *
     * @throws ApiClientException
     */
    protected function parseResponse(string $rawResponseBody): array
    {
        $parsedResponse = json_decode($rawResponseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiClientException(
                sprintf(
                    'Cannot parse API response. JSON parse error message: %1$s. Parsed string: %2$s',
                    json_last_error_msg(),
                    $rawResponseBody
                )
            );
        }

        if (isset($parsedResponse['errors'])) {
            throw new ApiClientException(
                sprintf(
                    'Errors returned by the API. Returned response: %1$s',
                    print_r($parsedResponse['errors'], true)
                )
            );
        }

        return $parsedResponse;
    }

    public function getAvailableSubscriptionPlans(): array
    {

        $api = eCurring_WC_Plugin::getApiHelper();
        $page_size = 50;
        $subscription_plans_response = json_decode($api->apiCall('GET', 'https://api.ecurring.com/subscription-plans?page[size]=' . $page_size), true);
        $subscription_plans_data = isset($subscription_plans_response['data']) ? (array) $subscription_plans_response['data'] : [];

        foreach ($subscription_plans_data as $subscription_plan) {
            if ($subscription_plan['attributes']['status'] == 'active' && $subscription_plan['attributes']['mandate_authentication_method'] == 'first_payment') {
                $subscription_plans[ $subscription_plan['id'] ] = $subscription_plan['attributes']['name'];
            }
        }

        if ($subscription_plans_response['links']['next']) {
            $last_page_link = parse_url($subscription_plans_response['links']['last']);
            parse_str($last_page_link['query'], $query);
            $last_page_num = $query['page']['number'];

            if ($last_page_num > 1) {
                for ($i = 2; $i <= $last_page_num; $i++) {
                    $next_page_response = json_decode($api->apiCall('GET', 'https://api.ecurring.com/subscription-plans?page[number]=' . $i . '&page[size]=' . $page_size), true);

                    if (isset($next_page_response['data'])) {
                        foreach ($next_page_response['data'] as $subscription_plan) {
                            if ($subscription_plan['attributes']['status'] == 'active' && $subscription_plan['attributes']['mandate_authentication_method'] == 'first_payment') {
                                $subscription_plans[ $subscription_plan['id'] ] = $subscription_plan['attributes']['name'];
                            }
                        }
                    }
                }
            }
        }


        return $subscription_plans;
    }
}
