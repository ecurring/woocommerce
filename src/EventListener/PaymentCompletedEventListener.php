<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use DateTime;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Customer\CustomerCrudException;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\EcurringException;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherInterface;
use eCurring_WC_Plugin;
use WC_Order;

/**
 * Activate subscription on payment complete.
 */
class PaymentCompletedEventListener implements EventListenerInterface
{
    /**
     * @var ApiClientInterface
     */
    protected $apiClient;
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
     * @var SubscriptionStatusSwitcherInterface
     */
    protected $subscriptionStatusSwitcher;

    /**
     * @param ApiClientInterface $apiClient To make eCurring API calls.
     * @param Subscriptions $subscriptionsApiClient Service able to send API requests
     *          related to subscriptions.
     * @param CustomerCrudInterface $customerCrud Service able to provide customer data.
     * @param SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher To activate subscription.
     * @param Repository $repository
     */
    public function __construct(
        ApiClientInterface $apiClient,
        Subscriptions $subscriptionsApiClient,
        CustomerCrudInterface $customerCrud,
        SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher,
        Repository $repository
    ) {

        $this->apiClient = $apiClient;
        $this->customerCrud = $customerCrud;
        $this->repository = $repository;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
        $this->subscriptionStatusSwitcher = $subscriptionStatusSwitcher;
    }

    public function init(): void
    {
        add_action('woocommerce_payment_complete', [$this, 'onPaymentComplete']);
    }

    /**
     * @param int $orderId
     *
     * @return void
     */
    public function onPaymentComplete(int $orderId): void
    {
        $order = wc_get_order($orderId);

        if (! $order instanceof WC_Order) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Payment completed for order %1$d, but that order not found.',
                    $orderId
                )
            );
            return;
        }

        $subscriptionId = $this->repository->findSubscriptionIdByOrderId($orderId);

        if (! $subscriptionId) {
            return;
        }

        eCurring_WC_Plugin::debug(
            sprintf(
                'Payment completed for order %1$d. Subscription id is %2$s, trying to activate it.',
                $order->get_id(),
                $subscriptionId
            )
        );
        $mandateAcceptedDate = $order->get_date_created() ?? new DateTime();
        $userId = $order->get_customer_id();

        try {
            if ($this->customerCrud->getFlagCustomerNeedsMollieMandate($userId)) {
                $this->addMollieMandateToTheCustomer($userId);
            }

            $this->subscriptionStatusSwitcher->activate($subscriptionId, $mandateAcceptedDate);
        } catch (EcurringException $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Couldn\'t activate eCurring subscription on successful Mollie payment.' .
                    ' An exception was caught when trying: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * Add Mollie mandate to the eCurring customer.
     *
     * @param int $localUserId Local id of the user that needs Mollie mandate to be added.
     *
     * @throws ApiClientException
     * @throws CustomerCrudException
     */
    protected function addMollieMandateToTheCustomer(int $localUserId): void
    {
        $mandateId = $this->customerCrud->getMollieMandateId($localUserId);
        $ecurringCustomerId = $this->customerCrud->getEcurringCustomerId($localUserId);
        $this->apiClient->addMollieMandateToTheEcurringCustomer($ecurringCustomerId, $mandateId);
        $this->customerCrud->saveFlagCustomerNeedsMollieMandate($localUserId, false);
    }
}
