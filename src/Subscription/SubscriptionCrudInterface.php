<?php

namespace Ecurring\WooEcurring\Subscription;

use WC_Order;
use WC_Product;

/**
 * Service able to save and get from the DB subscription data.
 */
interface SubscriptionCrudInterface {

	const MANDATE_ACCEPTED_DATE_FIELD = 'ecurring_woocommerce_mandate_accepted_date';

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

	/**
	 * Get eCurring subscription id by subscription product.
	 *
	 * @param WC_Product $product Product to get subscription ID from.
	 *
	 * @return string|null eCurring Subscription ID or null if no subscription exists for this product.
	 */
	public function getProductSubscriptionId( WC_Product $product);

	/**
	 * Get subscription id related to the given order.
	 *
	 * @param WC_Order $order Order to get subscription id from.
	 *
	 * @return string|null Subscription id or null if not exists.
	 */
	public function getSubscriptionIdByOrder(WC_Order $order);
}
