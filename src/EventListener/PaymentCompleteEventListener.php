<?php

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;

class PaymentCompleteEventListener {
	/**
	 * @var ApiClientInterface
	 */
	protected $apiClient;

	/**
	 * @param ApiClientInterface $apiClient To make eCurring API calls.
	 */
	public function __construct(ApiClientInterface $apiClient){

		$this->apiClient = $apiClient;
	}

	public function init()
	{
		add_action('woocommerce_payment_complete', [$this, 'onPaymentComplete']);
	}

	/**
	 * @param int $orderId
	 */
	public function onPaymentComplete(int $orderId)
	{
		//todo: find a better way to get a subscription id.
		$order = wc_get_order($orderId);
		$subscriptionId = $order->get_meta(SubscriptionCrudInterface::ECURRING_SUBSCRIPTION_ID_FIELD, true);

		$this->apiClient->activateSubscription($subscriptionId);
	}
}
