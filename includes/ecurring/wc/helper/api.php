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
     * @param string $subscription_id
     *
     * @return string Response body with subscription data or error message.
     */
    public function getSubscriptionById($subscription_id)
    {
        return $this->apiCall('GET','https://api.ecurring.com/subscriptions/'.$subscription_id);
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
