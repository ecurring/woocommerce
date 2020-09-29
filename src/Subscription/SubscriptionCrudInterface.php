<?php

namespace Ecurring\WooEcurring\Subscription;

use WC_Order;

/**
 * Service able to save and get from the DB subscription data.
 */
interface SubscriptionCrudInterface {

	/**
	 * Save subscription to the database.
	 *
	 * @param array    $subscriptionData Data to save.
	 * @param WC_Order $order The order subscription relates to.
	 */
	public function saveSubscription(array $subscriptionData, WC_Order $order);
}
