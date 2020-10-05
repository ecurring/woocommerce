<?php

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring_WC_Plugin;

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

	public function init(): void
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

		if($subscriptionId){
			try{
				$this->apiClient->activateSubscription($subscriptionId);
			} catch (ApiClientException $exception) {
				eCurring_WC_Plugin::debug(
					sprintf(
						'Could not activate subscription, API request failed. Order id: %1$d, subscription id: %2$s, error code: %3$d, error message: %4$s.',
						$orderId,
						$subscriptionId,
						$exception->getCode(),
						$exception->getMessage()
					)
				);
			}
		}

	}
}
