<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Customer\CustomerCrudException;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\EcurringException;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
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
     * @var SubscriptionCrudInterface
     */
    protected $subscriptionCrud;
    /**
     * @var CustomerCrudInterface
     */
    protected $customerCrud;

    /**
     * @param ApiClientInterface        $apiClient To make eCurring API calls.
     * @param SubscriptionCrudInterface $subscriptionCrud Service able to read subscription data.
     * @param CustomerCrudInterface     $customerCrud Service able to provide customer data.
     */
    public function __construct(
        ApiClientInterface $apiClient,
        SubscriptionCrudInterface $subscriptionCrud,
        CustomerCrudInterface $customerCrud
    ) {

        $this->apiClient = $apiClient;
        $this->subscriptionCrud = $subscriptionCrud;
        $this->customerCrud = $customerCrud;
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
                    'Payment completed for order %1$d, but this order not found.',
                    $orderId
                )
            );
            return;
        }

        $subscriptionId = $this->subscriptionCrud->getSubscriptionIdByOrder($order);

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
        $mandateAcceptedDate = $order->get_meta(SubscriptionCrudInterface::MANDATE_ACCEPTED_DATE_FIELD);
        $userId = $order->get_customer_id();

        if ($this->customerCrud->getFlagCustomerNeedsMollieMandate($userId)) {
            try {
                $this->addMollieMandateToTheCustomer($userId);
            } catch (EcurringException $exception) {
                eCurring_WC_Plugin::debug(
                    sprintf(
                        'Couldn\'t add Mollie mandate to the eCurring customer.' .
                        ' An exception caught when trying: %1$s',
                        $exception->getMessage()
                    )
                );
            }
        }

        try {
            $this->apiClient->activateSubscription($subscriptionId, $mandateAcceptedDate);
        } catch (ApiClientException $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Could not activate subscription, API request failed. Order id: %1$d, subscription id: %2$s, mandate accepted date: %3$s, error code: %4$d, error message: %5$s.',
                    $orderId,
                    $subscriptionId,
                    $mandateAcceptedDate,
                    $exception->getCode(),
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
