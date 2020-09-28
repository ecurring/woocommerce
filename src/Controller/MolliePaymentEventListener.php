<?php

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\ApiClientException;
use Mollie\Api\Resources\Payment;

/**
 * Listens for the Mollie payment create action.
 */
class MolliePaymentEventListener {

	/**
	 * @var ApiClient
	 */
	protected $apiClient;

	/**
	 * @var eCurring_WC_Helper_Data
	 */
	protected $dataHelper;

	/**
	 * MolliePaymentEventListener constructor.
	 *
	 * @param ApiClient $apiClient Service able to perform actions against eCurring API.
	 */
	public function __construct( ApiClient $apiClient, eCurring_WC_Helper_Data $dataHelper) {

		$this->apiClient = $apiClient;

		add_action(Mollie_WC_Plugin::PLUGIN_ID . '_payment_created', [$this, 'onMolliePaymentCreated']);
		$this->dataHelper = $dataHelper;
	}

	/**
	 * Create an eCurring subscription after Mollie payment created if at order contains at least one subscription
	 * product.
	 *
	 * @param Payment  $payment Created payment.
	 * @param WC_Order $order   The order payment created for.
	 */
	public function onMolliePaymentCreated(Payment $payment, WC_Order $order)
	{
		foreach($order->get_items() as $item){
			if($item instanceof \WC_Order_Item_Product) {
				$product = $item->get_product();
				if($this->isProductIsEcurringSubscription($product)) {
					$this->createEcurringSubscriptionForProduct($order, $product);
				}
			}
		}
	}

	/**
	 * Check if given product is eCurring subscription.
	 *
	 * @param WC_Product $product WC product to check.
	 *
	 * @return bool
	 */
	protected function isProductIsEcurringSubscription(WC_Product $product){
		return $product->meta_exists('_ecurring_subscription_plan');
	}

	/**
	 * Create an eCurring subscription on eCurring side using subscription product.
	 *
	 * @param WC_Product $product
	 *
	 * @throws ApiClientException If cannot create subscription.
	 */
	protected function createEcurringSubscriptionForProduct(WC_Order $order, WC_Product $product)
	{
		$this->apiClient->createSubscription(
			$this->dataHelper->getUsereCurringCustomerId($order),
			$product->get_meta('_ecurring_subscription_plan', true),
			add_query_arg( 'ecurring-webhook', 'subscription', home_url( '/' ) ),
			add_query_arg( 'ecurring-webhook', 'transaction', home_url( '/' ) )
		);
	}
}
