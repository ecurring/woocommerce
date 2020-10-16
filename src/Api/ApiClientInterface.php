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
	 * @param string $transactionWebhookUrl Webhook URL will be triggered by eCurring on transaction.
	 *
	 * @return array Created subscription data or error details.
	 *
	 * @throws ApiClientException If request failed.
	 */
	public function createSubscription(
		string $customerId,
		string $subscriptionId,
		string $transactionWebhookUrl = ''
	): array;

	/**
	 * @param string $subscriptionId
	 *
	 * @return array Subscription data or request error details.
	 *
	 * @throws ApiClientException If request failed.
	 */
	public function getSubscriptionById(string $subscriptionId): array;

	/**
	 * @param string $subscriptionId Id of the subscription to activate.
	 *
	 * @param string $mandateAcceptedDate Date string formatted according to ISO 8601.
	 *
	 * @return array Subscription data.
	 *
	 * @throws ApiClientException If request failed.
	 */
	public function activateSubscription(string $subscriptionId, string $mandateAcceptedDate): array;

}
