<?php

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring_WC_Helper_Data;
use eCurring_WC_Plugin;
use Exception;
use Mollie\Api\Resources\Payment;
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
	 * @var SubscriptionCrudInterface
	 */
	protected $subscriptionCrud;

	/**
	 * MolliePaymentEventListener constructor.
	 *
	 * @param ApiClient $apiClient Service able to perform actions against eCurring API.
	 */
	public function __construct(
		ApiClient $apiClient,
		eCurring_WC_Helper_Data $dataHelper,
		SubscriptionCrudInterface $subscriptionCrud
	) {

		$this->apiClient        = $apiClient;
		$this->dataHelper       = $dataHelper;
		$this->subscriptionCrud = $subscriptionCrud;
	}

	/**
	 * Init event listener.
	 */
	public function init(){
		add_action('mollie-payments-for-woocommerce_payment_created', [$this, 'onMolliePaymentCreated']);
	}

	/**
	 * Create an eCurring subscription after Mollie payment created if at order contains at least one subscription
	 * product.
	 *
	 * @param Payment  $payment Created payment.
	 * @param WC_Order $order   The order payment created for.
	 */
	public function onMolliePaymentCreated($payment, WC_Order $order ) {

		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				try {
					$product = $item->get_product();
					$subscriptionId = $this->subscriptionCrud->getProductSubscriptionId($product);
					if ( $subscriptionId !== null ) {
						$subscriptionData = $this->createEcurringSubscriptionForProduct( $order, $product );
						$this->subscriptionCrud->saveSubscription($subscriptionData, $order);
					}
				} catch ( Exception $exception ) {
					eCurring_WC_Plugin::debug(
						sprintf(
							'Failed to create subscription on successful Mollie payment. Caught exception with message: %1$s',
							$exception->getMessage()
						)
					);
				}
			}
		}
	}

	/**
	 * Create an eCurring subscription on eCurring side using subscription product.
	 *
	 * @param WC_Order $order
	 * @param string   $subscriptionId
	 *
	 * @return array
	 * @throws ApiClientException
	 */
	protected function createEcurringSubscriptionForProduct( WC_Order $order, $subscriptionId ) {

		return $this->apiClient->createSubscription(
			$this->dataHelper->getUsereCurringCustomerId( $order ),
			$subscriptionId,
			add_query_arg( 'ecurring-webhook', 'subscription', home_url( '/' ) ),
			add_query_arg( 'ecurring-webhook', 'transaction', home_url( '/' ) )
		);
	}
}
