<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Api;

/**
 * Service able to interact with eCurring API.
 */
interface ApiClientInterface {

	/**
	 * API call to create subscription.
	 *
	 * @param string $customerId eCurring customer id.
	 * @param string $subscriptionId eCurring subscription product id.
	 * @param string $subscriptionWebhookUrl Webhook URL will be triggered by eCurring on subscription changes.
	 * @param string $transactionWebhookUrl Webhook URL will be triggered by eCurring on transaction.
	 *
	 * @return array Created subscription data or error details.
	 */
	public function createSubscription(
		string $customerId,
		string $subscriptionId,
		string $subscriptionWebhookUrl = '',
		string $transactionWebhookUrl = ''
	): array;

	/**
	 * @param string $subscriptionId
	 *
	 * @return array Subscription data or request error details.
	 */
	public function getSubscriptionById(string $subscriptionId): array;
}
