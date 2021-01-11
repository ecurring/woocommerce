<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\EcurringException;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;
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
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @var CustomerCrudInterface
     */
    protected $customerCrud;
    /**
     * @var DataBasedSubscriptionFactoryInterface
     */
    protected $dataBasedSubscriptionFactory;
    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var Subscriptions
     */
    protected $subscriptionsApiClient;

    /**
     * MollieMandateCreatedEventListener constructor.
     *
     * @param ApiClient $apiClient Service able to perform actions against eCurring API.
     * @param Subscriptions $subscriptionsApiClient
     * @param DataBasedSubscriptionFactoryInterface $dataBasedSubscriptionFactory
     * @param Repository $repository
     * @param CustomerCrudInterface $customerCrud
     */
    public function __construct(
        ApiClient $apiClient,
        Subscriptions $subscriptionsApiClient,
        DataBasedSubscriptionFactoryInterface $dataBasedSubscriptionFactory,
        Repository $repository,
        CustomerCrudInterface $customerCrud
    ) {

        $this->apiClient = $apiClient;
        $this->customerCrud = $customerCrud;
        $this->dataBasedSubscriptionFactory = $dataBasedSubscriptionFactory;
        $this->repository = $repository;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
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

        try {
            /**
             * This needed to prevent possible situation when customer returns to the previous page
             * after submitting checkout form. In this case, this code may be executed again
             * so another subscription would be created.
             *
             * @see https://inpsyde.atlassian.net/browse/ECUR-20 for details.
             */
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
        $response = $this->apiClient->createCustomer([
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'language' => 'en',
            'external_id' => $mollieCustomerId,
        ]);

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

        return (string) $product->get_meta('_ecurring_subscription_plan');
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
        $subscriptionId = $this->repository->findSubscriptionIdByOrderId($order->get_id());

        if ($subscriptionId === null) {
            return false;
        }

        try {
            $subscriptionData = $this->apiClient->getSubscriptionById($subscriptionId);
        } catch (ApiClientException $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to check if subscription %1$s exists, caught API client exception. Exception message: %2$s',
                    $subscriptionId,
                    $exception->getMessage()
                )
            );
        }

        return isset($subscriptionData['data']['type']) &&
            $subscriptionData['data']['type'] === 'subscription';
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
        foreach ($order->get_items() as $item) {
            $subscriptionPlanId = $this->getSubscriptionPlanIdByOrderItem($item);
            if ($subscriptionPlanId !== '' && $ecurringCustomerId !== '') {
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
    protected function handleSubscriptionCreating(string $ecurringCustomerId, string $subscriptionPlanId, int $subscriptionOrderId): void
    {
        try {
            $subscription = $this->subscriptionsApiClient->create($ecurringCustomerId, $subscriptionPlanId);
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

    /**
     * Create an eCurring subscription on eCurring side.
     *
     * @param string $ecurringCustomerId id of the customer in eCurring.
     * @param string $subscriptionId id of the subscription plan in eCurring.
     *
     * @return SubscriptionInterface Created subscription instance.
     *
     * @throws ApiClientException
     * @throws SubscriptionFactoryException
     */
    protected function createEcurringSubscription(string $ecurringCustomerId, string $subscriptionId): SubscriptionInterface
    {
        return $this->subscriptionsApiClient->create(
            $ecurringCustomerId,
            $subscriptionId,
            [
                'transaction_webhook_url' => add_query_arg(
                    'ecurring-webhook',
                    'transaction',
                    home_url('/')
                ),
            ]
        );
    }
}
