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

	public function createSubscription(array $data, WC_Order $order)
    {
        $data = apply_filters( 'woocommerce_' . $this->id . '_args', $data, $order );
        do_action( WOOECUR_PLUGIN_ID . '_create_payment', $data, $order );

        return $this->apiCall( 'POST', 'https://api.ecurring.com/subscriptions', $data );
    }

	/**
	 * @param $method
	 * @param $url
	 * @param $apiKey
	 * @param bool|array $data
	 *
	 * @return mixed
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
