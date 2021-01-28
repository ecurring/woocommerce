<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\EcurringException;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use eCurring_WC_Helper_Data;
use eCurring_WC_Plugin;
use Mollie\Api\Resources\Payment;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;

/**
 * Listens for the Mollie payment create action.
 */
class MollieMandateCreatedEventListener implements EventListenerInterface
{

    /**
     * @var CustomerCrudInterface
     */
    protected $customerCrud;
    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var Subscriptions
     */
    protected $subscriptionsApiClient;
    /**
     * @var Customers
     */
    protected $customersApiClient;
    /**
     * @var eCurring_WC_Helper_Data
     */
    protected $dataHelper;

    /**
     * MollieMandateCreatedEventListener constructor.
     *
     * @param Subscriptions $subscriptionsApiClient
     * @param Customers $customersApiClient
     * @param Repository $repository
     * @param CustomerCrudInterface $customerCrud
     * @param eCurring_WC_Helper_Data $dataHelper
     */
    public function __construct(
        Subscriptions $subscriptionsApiClient,
        Customers $customersApiClient,
        Repository $repository,
        CustomerCrudInterface $customerCrud,
        eCurring_WC_Helper_Data $dataHelper
    ) {

        $this->customerCrud = $customerCrud;
        $this->repository = $repository;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
        $this->customersApiClient = $customersApiClient;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Init event listener.
     */
    public function init(): void
    {

        add_action(
            'mollie-payments-for-woocommerce_after_mandate_created',
            [$this, 'onMollieMandateCreated'],
            10,
            4
        );
    }

    /**
     * Create an eCurring subscription after Mollie mandate created if at order
     * contains at least one subscription product.
     *
     * @param Payment  $payment Created payment.
     * @param WC_Order $order   The order payment created for.
     * @param string   $mollieCustomerId
     * @param string   $mandateId
     *
     */
    public function onMollieMandateCreated($payment, WC_Order $order, string $mollieCustomerId, string $mandateId): void
    {
        try {
            /**
             * This needed to prevent possible situation when customer returns to the previous page
             * after submitting checkout form. In this case, this code may be executed again
             * so another subscription would be created.
             *
             * @see https://inpsyde.atlassian.net/browse/ECUR-20 for details.
             */
            if ($this->subscriptionForOrderExists($order)) {
                eCurring_WC_Plugin::debug(
                    sprintf(
                        'Subscription already exists for order %1$d.' .
                        ' New subscription will not be created.',
                        $order->get_id()
                    )
                );

                return;
            }

            $ecurringCustomerId = $this->customerCrud->getEcurringCustomerId($order->get_customer_id());

            if (! $ecurringCustomerId) {
                $ecurringCustomerId = $this->createEcurringCustomerConnectedToMollieCustomer($mollieCustomerId, $order);
                $this->customerCrud->saveMollieMandateId($order->get_customer_id(), $mandateId);
                $this->customerCrud->saveEcurringCustomerId($order->get_customer_id(), $ecurringCustomerId);

                eCurring_WC_Plugin::debug('eCurring customer not found, a new one was created.');
            }

            $this->createEcurringSubscriptionsFromOrder($order, $ecurringCustomerId);
        } catch (EcurringException $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to create subscription on successful Mollie payment. ' .
                    'Caught exception with message: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * Create a new eCurring user and attach a Mollie mandate.
     *
     * @param string   $mollieCustomerId Mollie customer id to connect to new user.
     * @param WC_Order $order To take customer data from.
     *
     * @return string Created eCurring customer ID.
     *
     * @throws ApiClientException If customer creating failed.
     */
    public function createEcurringCustomerConnectedToMollieCustomer(string $mollieCustomerId, WC_Order $order): string
    {
        $customerAttributes = $this->dataHelper->customerAttributesFromOrder($order);
        $customerAttributes['external_id'] = $mollieCustomerId;

        $response = $this->customersApiClient->createCustomer($customerAttributes);

        return (string) $response['data']['id'];
    }

    /**
     * Get subscription plan id from WC order item.
     *
     * @param WC_Order_Item $item Item to get subscription plan id from.
     *
     * @return string Subscription id or empty string if not exists.
     */
    protected function getSubscriptionPlanIdByOrderItem(WC_Order_Item $item): string
    {
        if (! $item instanceof WC_Order_Item_Product) {
            return '';
        }

        $product = $item->get_product();

        $subscriptionPlanMetaValue = $product->get_meta('_ecurring_subscription_plan');

        return $subscriptionPlanMetaValue === '0' ? '' : strval($subscriptionPlanMetaValue);
    }

    /**
     * Check if subscription already created for given order.
     *
     * @param WC_Order $order
     *
     * @return bool
     *
     * @throws ApiClientException
     */
    protected function subscriptionForOrderExists(WC_Order $order): bool
    {
        $subscriptionId = $this->repository->findSubscriptionIdByOrderId($order->get_id());

        if ($subscriptionId === '') {
            return false;
        }

        return $this->subscriptionsApiClient->subscriptionExists($subscriptionId);
    }

    /**
     * Create eCurring subscriptions for subscription products in order.
     *
     * @param WC_Order $order Order to create subscriptions for.
     * @param string $ecurringCustomerId The eCurring customer ID to create subscription for.
     *
     * @throws ApiClientException If problems occurred when tried to create.
     */
    public function createEcurringSubscriptionsFromOrder(WC_Order $order, string $ecurringCustomerId): void
    {
        if ($ecurringCustomerId === '') {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to create a subscription from order %1$d: the eCurring customer id is empty.',
                    $order->get_id()
                )
            );

            return;
        }

        foreach ($order->get_items() as $item) {
            $subscriptionPlanId = $this->getSubscriptionPlanIdByOrderItem($item);
            if ($subscriptionPlanId !== '') {
                $this->handleSubscriptionCreating($ecurringCustomerId, $subscriptionPlanId, $order->get_id());
            }
        }
    }

    /**
     * @param string $ecurringCustomerId
     * @param string $subscriptionPlanId
     * @param int $subscriptionOrderId
     *
     * @throws ApiClientException
     */
    protected function handleSubscriptionCreating(
        string $ecurringCustomerId,
        string $subscriptionPlanId,
        int $subscriptionOrderId
    ): void {
        try {
            $attributes = [
                'metadata' => json_encode([
                    'source' => 'WooCommerce',
                    'shop_url' => get_site_url(get_current_blog_id()),
                    'order_id' => $subscriptionOrderId,
                ]),
            ];
            $subscription = $this->subscriptionsApiClient->create($ecurringCustomerId, $subscriptionPlanId, $attributes);
            $this->repository->insert($subscription, $subscriptionOrderId);
            eCurring_WC_Plugin::debug(
                'A new eCurring subscription was successfully created.'
            );
        } catch (SubscriptionFactoryException $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Couldn\'t create subscription from the API response.' .
                    ' Exception caught: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }
}
