<?php

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use eCurring_WC_Helper_Data;
use eCurring_WC_Plugin;
use Exception;
use Mollie\Api\Resources\Payment;
use Mollie_WC_Plugin;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

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
		$this->dataHelper = $dataHelper;
	}

	/**
	 * Init event listener.
	 */
	public function init(){
		add_action(Mollie_WC_Plugin::PLUGIN_ID . '_payment_created', [$this, 'onMolliePaymentCreated']);
	}

	/**
	 * Create an eCurring subscription after Mollie payment created if at order contains at least one subscription
	 * product.
	 *
	 * @param Payment  $payment Created payment.
	 * @param WC_Order $order   The order payment created for.
	 */
	public function onMolliePaymentCreated( Payment $payment, WC_Order $order ) {

		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				try {
					$product = $item->get_product();
					if ( $this->isProductIsEcurringSubscription( $product ) ) {
						$this->createEcurringSubscriptionForProduct( $order, $product );
					}
				} catch ( Exception $exception ) {
					eCurring_WC_Plugin::debug(
						sprintf(
							'Failed to create subscription on successfull Mollie payment. Caught exception with message: %1$s',
							$exception->getMessage()
						)
					);
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
	 * @return array
	 * @throws ApiClientException
	 */
	protected function createEcurringSubscriptionForProduct( WC_Order $order, WC_Product $product ) {

		return $this->apiClient->createSubscription(
			$this->dataHelper->getUsereCurringCustomerId( $order ),
			$product->get_meta( '_ecurring_subscription_plan', true ),
			add_query_arg( 'ecurring-webhook', 'subscription', home_url( '/' ) ),
			add_query_arg( 'ecurring-webhook', 'transaction', home_url( '/' ) )
		);
	}
}
