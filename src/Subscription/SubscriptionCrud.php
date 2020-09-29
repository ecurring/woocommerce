<?php

namespace Ecurring\WooEcurring\Subscription;

use WC_Order;

class SubscriptionCrud {

	/**
	 * Save subscription data to the database.
	 *
	 * @param array    $subscriptionData Subscription to save.
	 * @param WC_Order $subscriptionOrder The order subscription should be associated with.
	 */
	public function saveSubscription(array $subscriptionData, WC_Order $subscriptionOrder)
	{
		$subscriptionId = $subscriptionData['data']['id'];

		$subscriptionOrder->add_meta_data('ecurring_woocommerce_mandate_accepted_date', date( 'Y-m-d H:i:s' ));
		$subscriptionOrder->add_meta_data('_ecurring_subscription_id', $subscriptionId);
		$confirmationPage = $subscriptionData['data']['attributes']['confirmation_page'];

		//todo: find a better way to do it.
		$subscriptionLink = 'https://app.ecurring.com/subscriptions/'.explode('/',$confirmationPage)[5];
		$subscriptionOrder->add_meta_data('_ecurring_subscription_link', $subscriptionLink);

		$subscriptionOrder->add_order_note( sprintf(
		/* translators: Placeholder 1: Payment method title, placeholder 2: payment ID */
			__( 'Payment started for subscription ID %s.', 'woo-ecurring' ),
			'<a href="'.$subscriptionLink.'" target="_blank">'. $subscriptionId .'</a>'
		) );

		$subscriptionOrder->save();
	}
}
