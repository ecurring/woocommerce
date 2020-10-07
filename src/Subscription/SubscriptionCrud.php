<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use WC_Order;
use WC_Product;

class SubscriptionCrud implements SubscriptionCrudInterface {

	/**
	 * Save subscription data to the database.
	 *
	 * @param array    $subscriptionData Subscription to save.
	 * @param WC_Order $subscriptionOrder The order subscription should be associated with.
	 */
	public function saveSubscription(array $subscriptionData, WC_Order $subscriptionOrder)
	{
		$subscriptionId = $subscriptionData['data']['id'];

		$subscriptionOrder->update_meta_data(self::MANDATE_ACCEPTED_DATE_FIELD, date( 'c' ));
		$subscriptionOrder->update_meta_data(self::ECURRING_SUBSCRIPTION_ID_FIELD, $subscriptionId);

		$subscriptionLink = $this->buildSubscriptionUrl($subscriptionData['links']['self']);
		$subscriptionOrder->update_meta_data(self::ECURRING_SUBSCRIPTION_LINK_FIELD, $subscriptionLink);

		$subscriptionOrder->add_order_note( sprintf(
		/* translators: Placeholder 1: Payment method title, placeholder 2: payment ID */
			__( 'Payment started for subscription ID %s.', 'woo-ecurring' ),
			'<a href="'.$subscriptionLink.'" target="_blank">'. $subscriptionId .'</a>'
		) );

		$subscriptionOrder->save();
	}

	/**
	 * @inheritDoc
	 */
	public function getProductSubscriptionId( WC_Product $product) {
		$subscriptionId = $product->get_meta( self::ECURRING_SUBSCRIPTION_PLAN_FIELD, true );

		//Previously plugin saved subscription id '0' for non-eCurring products.
		return $subscriptionId ?: null;
	}

	/**
	 * @inheritDoc
	 */
	public function getSubscriptionIdByOrder( WC_Order $order )
	{
		$subscriptionId = $order->get_meta(self::ECURRING_SUBSCRIPTION_ID_FIELD, true);

		return $subscriptionId ?: null;
	}

	/**
	 * Return url of the subscription page.
	 *
	 * @param string $subscriptionApiUrl URL of subscription JSON API.
	 *
	 * @return string Url of the subscription page.
	 */
	protected function buildSubscriptionUrl(string $subscriptionApiUrl) {
		$id = basename($subscriptionApiUrl);

		return sprintf(
			'https://app.ecurring.com/subscriptions/%1$s',
			$id
		);

	}
}
