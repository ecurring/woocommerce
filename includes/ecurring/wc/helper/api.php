<?php

class eCurring_WC_Helper_Api
{
	/**
	 * @var eCurring_WC_Helper_Settings
	 */
	protected $settings_helper;

	/**
	 * @param eCurring_WC_Helper_Settings $settings_helper
	 */
	public function __construct(eCurring_WC_Helper_Settings $settings_helper) {
		$this->settings_helper = $settings_helper;
	}

    /**
     * Send request for creating new eCurring subscription.
     *
     * @param array $data
     *
     * @return string API response body or error message.
     */
	public function createSubscription(array $data)
    {
        return $this->apiCall( 'POST', 'https://api.ecurring.com/subscriptions', $data );
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
     * @throws eCurring_WC_Exception_ApiClientException If cannot parse response.
     */
    public function getSubscriptionById($subscription_id)
    {
        $url = 'https://api.ecurring.com/subscriptions/'.$subscription_id;

        $subscriptionData = $this->doApiCallWithResultParsing('GET', $url);

        if(isset($subscriptionData['errors'])){
            throw new eCurring_WC_Exception_ApiClientException(
                'Errors returned by the API. Returned response: %1$s',
                print_r($subscriptionData, true)
            );
        }

        return $subscriptionData;
    }

    /**
     * Do API call and parse results.
     *
     * This function is needed to call existing apiCall, parse results and throw en exception if something wrong.
     * That function exists for backward compatibility and should be removed or updated in the future.
     *
     * @param string     $method HTTP Method, one of the GET, POST, PATH, DELETE.
     * @param string     $url    Request target URL.
     * @param bool|array $data   Content to be sent in JSON-encoded format as request body.
     *
     * @return array Response data.
     *
     * @throws eCurring_WC_Exception_ApiClientException If cannot parse response.
     */
    protected function doApiCallWithResultParsing($method, $url, $data = false)
    {
        $rawResponseBody = $this->apiCall($method, $url, $data);
        $parsedResponse = json_decode($rawResponseBody, true);

        if(json_last_error() !== JSON_ERROR_NONE){
            throw new eCurring_WC_Exception_ApiClientException(
                sprintf(
                    'Cannot parse API response. JSON parse error message: %1$s. Parsed string: %2$s',
                    json_last_error_msg(),
                    $rawResponseBody
                )
            );
        }

        return $parsedResponse;
    }

	/**
     * Make eCurring API request call.
     *
	 * @param string $method HTTP Method, one of the GET, POST, PATH, DELETE.
	 * @param string $url Request target URL.
	 * @param bool|array $data Content to be sent in JSON-encoded format as request body.
	 *
	 * @return string Response body or error message string.
	 */
	public function apiCall( $method, $url, $data = false ) {

		$settings_helper = eCurring_WC_Plugin::getSettingsHelper();
		$api_key         = $settings_helper->getApiKey();

		$WP_Http = new WP_Http();
		$args    = array (
			'method'  => $method,
			'headers' => array (
				'X-Authorization' => $api_key,
				'Content-Type'    => 'application/vnd.api+json',
				'Accept'          => 'application/vnd.api+json'
			),
			'body'    => $data ? json_encode( $data ) : ''
		);

		$result = $WP_Http->request( $url, $args );

		if(is_wp_error($result)) {
            return $result->get_error_message();
        }

		return $result['body'];
	}
}
