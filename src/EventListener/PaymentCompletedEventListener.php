<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
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

        $mandateId = $this->customerCrud->getMollieMandateId($order->get_customer_id());
        $ecurringCustomerId = $this->customerCrud->getEcurringId($order->get_customer_id());

        try {
            $this->apiClient->addMollieMandateToTheEcurringCustomer($ecurringCustomerId, $mandateId);
        } catch (ApiClientException $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Cannot add Mollie mandate to the eCurring user. Caught exception: %1$s',
                    $exception->getMessage()
                )
            );
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
}
