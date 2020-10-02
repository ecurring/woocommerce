<?php

namespace Ecurring\WooEcurring\Api;

class ApiClient {

	/**
	 * @var string
	 */
	protected $apiKey;

	/**
	 * @param string $apiKey Key required for authentication.
	 */
	public function __construct($apiKey) {
		$this->apiKey = $apiKey;
	}

	/**
	 * Send request for creating new eCurring subscription and activating it immediately.
	 *
	 * @param        $ecurringCustomerId
	 * @param        $subscriptionPlanId
	 * @param string $subscriptionWebhookUrl
	 * @param string $transactionWebhookUrl
	 *
	 * @return array API response body or error message.
	 * @throws ApiClientException
	 */
	public function createSubscription(
		$ecurringCustomerId,
		$subscriptionPlanId,
		$subscriptionWebhookUrl = '',
		$transactionWebhookUrl = ''
	) {
		$requestData = [
			'data' => [
				'type'       => 'subscription',
				'attributes' => [
					'customer_id'              => $ecurringCustomerId,
					'subscription_plan_id'     => $subscriptionPlanId,
					'subscription_webhook_url' => $subscriptionWebhookUrl,
					'transaction_webhook_url'  => $transactionWebhookUrl, //todo: check if we still need this
					'confirmation_sent'        => 'true', // todo: check if we need this,
					'mandate_accepted'         => 'true',
					'mandate_accepted_date'    => date('c'),
					'status'                   => 'active',
					'metadata'                 => ['source' => 'woocommerce']
				]
			]
		];

		return $this->apiCall(
			'POST',
			'https://api.ecurring.com/subscriptions',
			$requestData
		);
	}

	/**
	 * Get subscription data using subscription id.
	 *
	 * @param string $subscription_id The id of subscription to get from eCurring API.
	 *
	 * @return array Subscription data.
	 *
	 * @see https://docs.ecurring.com/subscriptions/get Response data structure.
	 *
	 * @throws ApiClientException If cannot parse response.
	 */
	public function getSubscriptionById($subscription_id)
	{
		$url = 'https://api.ecurring.com/subscriptions/'.$subscription_id;

		return $this->apiCall('GET', $url);
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
	public function apiCall( $method, $url, $data = false ) {

		$rawResponse = $this->doApiRequest($method, $url, $data);

		if(is_wp_error($rawResponse)){
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
	 * @param false $data
	 *
	 * @return mixed
	 */
	protected function doApiRequest($method, $url, $data = false){
		$args    = array (
			'method'  => $method,
			'headers' => array (
				'X-Authorization' => $this->apiKey,
				'Content-Type'    => 'application/vnd.api+json',
				'Accept'          => 'application/vnd.api+json'
			),
			'body'    => $data ? json_encode( $data ) : ''
		);

		return wp_remote_request( $url, $args );
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
	protected function parseResponse($rawResponseBody)
	{
		$parsedResponse = json_decode($rawResponseBody, true);

		if(json_last_error() !== JSON_ERROR_NONE){
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
}
