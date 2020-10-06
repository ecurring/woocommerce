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
		add_action(
			'mollie-payments-for-woocommerce_payment_created',
			[$this, 'onMolliePaymentCreated'],
			10,
			2
		);
	}

	/**
	 * Create an eCurring subscription after Mollie payment created if at order contains at least one subscription
	 * product.
	 *
	 * @param Payment  $payment Created payment.
	 * @param WC_Order $order   The order payment created for.
	 */
	public function onMolliePaymentCreated($payment, WC_Order $order ) {

		if( $this->subscriptionForOrderExists($order) )
		{
			eCurring_WC_Plugin::debug(
				sprintf(
					'Subscription already exists for order %1$d. New subscription will not be created.',
					$order->get_id()
				)
			);

			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				try {
					$product = $item->get_product();
					$subscriptionId = $this->subscriptionCrud->getProductSubscriptionId($product);

					if ( $subscriptionId !== null ) {
						$subscriptionData = $this->createEcurringSubscription( $order, $subscriptionId );
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
	 * Check if subscription already created for given order.
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	protected function subscriptionForOrderExists(WC_Order $order): bool
	{
		$subscriptionId = $this->subscriptionCrud->getSubscriptionIdByOrder($order);

		if($subscriptionId === null){
			return false;
		}

		return isset($subscriptionData['data']['type']) && $subscriptionData['data']['type'] === 'subscription';
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
	protected function createEcurringSubscription( WC_Order $order, $subscriptionId ) {

		return $this->apiClient->createSubscription(
			$this->dataHelper->getUsereCurringCustomerId( $order ),
			$subscriptionId,
			add_query_arg( 'ecurring-webhook', 'subscription', home_url( '/' ) ),
			add_query_arg( 'ecurring-webhook', 'transaction', home_url( '/' ) )
		);
	}
}
