<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring_WC_Helper_Data;
use eCurring_WC_Plugin;
use Exception;
use Mollie\Api\Resources\Payment;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;

/**
 * Listens for the Mollie payment create action.
 */
class MollieMandateCreatedEventListener implements EventListenerInterface {

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
	 * MollieMandateCreatedEventListener constructor.
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
	public function init(): void{
		add_action(
			'mollie-payments-for-woocommerce_after_mandate_created',
			[$this, 'onMollieMandateCreated'],
			10,
			4
		);
	}

    /**
     * Create an eCurring subscription after Mollie mandate created if at order contains at least one subscription
     * product.
     *
     * @param Payment  $payment Created payment.
     * @param WC_Order $order   The order payment created for.
     * @param string   $mollieCustomerId
     * @param string   $mandateId
     *
     */
	public function onMollieMandateCreated($payment, WC_Order $order, string $mollieCustomerId, string $mandateId ): void
    {
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

		$ecurringCustomerId = $this->getEcurringCustomerIdByOrder($order);

		if($ecurringCustomerId === null){
		    $ecurringCustomerId = $this->createEcurringCustomerWithMollieMandate($mollieCustomerId, $order);
        }

		foreach ( $order->get_items() as $item ) {
		    $subscriptionId = $this->getSubscriptionPlanIdByOrderItem($item);
            if ( $subscriptionId !== null ) {
                try {
                    $subscriptionData = $this->createEcurringSubscription($order, $subscriptionId);
                    $this->subscriptionCrud->saveSubscription($subscriptionData, $order);
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
     * Return eCurring customer id associated with order customer or null if not found.
     *
     * @param WC_Order $order
     *
     * @return string|null
     */
	public function getEcurringCustomerIdByOrder(WC_Order $order): ?string
    {
        $ecurringCustomerId = get_user_meta($order->get_customer_id(), 'ecurring_customer_id', true);

        return $ecurringCustomerId === false ? null : (string) $ecurringCustomerId;
    }

    /**
     * Create a new ecurring user and attach a Mollie mandate.
     *
     * @param string   $mollieCustomerId Mollie customer id to connect to new user.
     * @param WC_Order $order To take customer data from.
     *
     * @return string Created eCurring customer ID.
     */
    public function createEcurringCustomerWithMollieMandate(string $mollieCustomerId, WC_Order $order): string
    {
        $response = $this->apiClient->createCustomer([
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'language' => 'en',
            'external_id' => $mollieCustomerId
        ]);

        return (string) $response['data']['id'];
    }

    /**
     * Get subscription plan id from WC order item.
     *
     * @param WC_Order_Item $item Item to get subscription plan id from.
     *
     * @return string|null Subscription id or null if not exists.
     */
	protected function getSubscriptionPlanIdByOrderItem(WC_Order_Item $item): ?string
    {
        if(! $item instanceof WC_Order_Item_Product) {
            return null;
        }

        $product = $item->get_product();

        return $this->subscriptionCrud->getProductSubscriptionId($product);
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

		try{
			$subscriptionData = $this->apiClient->getSubscriptionById($subscriptionId);
		} catch (ApiClientException $exception)
		{
			eCurring_WC_Plugin::debug(
				sprintf(
					'Failed to check if subscription %1$s exists, caught API client exception. Exception message: %2$s',
					$subscriptionId,
					$exception->getMessage()
				)
			);
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
	protected function createEcurringSubscription( WC_Order $order, string $subscriptionId ): array
    {

		return $this->apiClient->createSubscription(
			$this->dataHelper->getUsereCurringCustomerId( $order ),
			$subscriptionId,
			add_query_arg( 'ecurring-webhook', 'transaction', home_url( '/' ) )
		);
	}
}
