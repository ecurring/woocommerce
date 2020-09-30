<?php

namespace Ecurring\WooEcurring\Subscription;

use WC_Order;

/**
 * Service able to save and get from the DB subscription data.
 */
interface SubscriptionCrudInterface {

	const MANDATE_ACCEPTED_FIELD = 'ecurring_woocommerce_mandate_accepted_date';

	const ECURRING_SUBSCRIPTION_ID_FIELD = '_ecurring_subscription_id';

	const ECURRING_SUBSCRIPTION_LINK_FIELD = '_ecurring_subscription_link';

	const ECURRING_SUBSCRIPTION_PLAN_FIELD = '_ecurring_subscription_plan';

	/**
	 * Save subscription to the database.
	 *
	 * @param array    $subscriptionData Data to save.
	 * @param WC_Order $order The order subscription relates to.
	 */
	public function saveSubscription(array $subscriptionData, WC_Order $order);
}
