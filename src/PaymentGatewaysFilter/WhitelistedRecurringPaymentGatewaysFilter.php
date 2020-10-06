<?php
declare(strict_types=1);

namespace Ecurring\WooEcurring\PaymentGatewaysFilter;

use WC_Payment_Gateway;

/**
 * Filter WooCommerce payment gateways, return only whitelisted and supporting 'subscriptions' ones.
 */
class WhitelistedRecurringPaymentGatewaysFilter implements PaymentGatewaysFilterInterface {

	/**
	 * @var string[] List of allowed payment gateway classes.
	 */
	protected $whiteList;

	/**
	 * @param string[] $whiteList White list of payment gateway class names.
	 */
	public function __construct(array $whiteList)
	{
		$this->whiteList = $whiteList;
	}

	/**
	 * @inheritDoc
	 */
	public function filter( array $gateways ): array {
		return array_filter($gateways, function(WC_Payment_Gateway $gateway){
			return $this->isGatewayClassWhitelisted($gateway) && $gateway->supports('subscriptions');
		});
	}

	/**
	 * Check if gateway is in the white list.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway to check.
	 *
	 * @return bool
	 */
	protected function isGatewayClassWhitelisted( WC_Payment_Gateway $gateway ) {
		return in_array(get_class($gateway), $this->whiteList, true);
	}
}
