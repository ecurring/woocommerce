<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Customer\CustomerCrudException;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\EcurringException;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
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
     * @var SubscriptionCrudInterface
     */
    protected $subscriptionCrud;
    /**
     * @var CustomerCrudInterface
     */
    protected $customerCrud;

    /**
     * MollieMandateCreatedEventListener constructor.
     *
     * @param ApiClient $apiClient Service able to perform actions against eCurring API.
     * @param SubscriptionCrudInterface $subscriptionCrud
     * @param CustomerCrudInterface $customerCrud
     */
    public function __construct(
        ApiClient $apiClient,
        SubscriptionCrudInterface $subscriptionCrud,
        CustomerCrudInterface $customerCrud
    ) {

        $this->apiClient = $apiClient;
        $this->subscriptionCrud = $subscriptionCrud;
        $this->customerCrud = $customerCrud;
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
                $this->customerCrud->saveEcurringId($order->get_customer_id(), $ecurringCustomerId);

                eCurring_WC_Plugin::debug('eCurring customer not found, a new one was created.');
            }

            $this->createEcurringSubscriptionsFromOrder($order);
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
     * @return string Subscription id or null if not exists.
     */
    protected function getSubscriptionPlanIdByOrderItem(WC_Order_Item $item): string
    {
        if (! $item instanceof WC_Order_Item_Product) {
            return '';
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
     *
     * @throws ApiClientException|CustomerCrudException If problems occurred when tried to create.
     */
    public function createEcurringSubscriptionsFromOrder(WC_Order $order): void
    {
        $ecurringCustomerId = $this->customerCrud->getEcurringCustomerId($order->get_customer_id());

        foreach ($order->get_items() as $item) {
            $subscriptionId = $this->getSubscriptionPlanIdByOrderItem($item);
            if ($subscriptionId !== '' && $ecurringCustomerId !== '') {
                $subscriptionData = $this->createEcurringSubscription($ecurringCustomerId, $subscriptionId);
                $this->subscriptionCrud->saveSubscription($subscriptionData, $order);

                eCurring_WC_Plugin::debug('A new eCurring subscription was successfully created.');
            }
        }
    }

    /**
     * Create an eCurring subscription on eCurring side using subscription product.
     *
     * @param string $ecurringCustomerId
     * @param string $subscriptionId
     *
     * @return array Saved subscription data.
     *
     * @throws ApiClientException
     */
    protected function createEcurringSubscription(string $ecurringCustomerId, string $subscriptionId): array
    {
        return $this->apiClient->createSubscription(
            $ecurringCustomerId,
            $subscriptionId,
            add_query_arg('ecurring-webhook', 'transaction', home_url('/'))
        );
    }
}
