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
	 * @var SubscriptionCrudInterface
	 */
	protected $subscriptionCrud;

	/**
	 * @param ApiClientInterface        $apiClient To make eCurring API calls.
	 * @param SubscriptionCrudInterface $subscriptionCrud Service able to read subscription data.
	 */
	public function __construct(ApiClientInterface $apiClient, SubscriptionCrudInterface $subscriptionCrud){

		$this->apiClient = $apiClient;
		$this->subscriptionCrud = $subscriptionCrud;
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
		$order = wc_get_order($orderId);
		$subscriptionId = $this->subscriptionCrud->getSubscriptionIdByOrder($order);

		if($subscriptionId){
			try{
				$this->apiClient->activateSubscription($subscriptionId);
			} catch (ApiClientException $exception) {
				//todo: change order status?
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
