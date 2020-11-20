<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring_WC_Plugin;
use WC_Order;

class MolliePaymentCreatedEventListener implements EventListenerInterface
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
     * @param ApiClientInterface        $apiClient To make eCurring API calls.
     * @param SubscriptionCrudInterface $subscriptionCrud Service able to read subscription data.
     */
    public function __construct(ApiClientInterface $apiClient, SubscriptionCrudInterface $subscriptionCrud)
    {

        $this->apiClient = $apiClient;
        $this->subscriptionCrud = $subscriptionCrud;
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

        try {
            $this->apiClient->activateSubscription($subscriptionId, $mandateAcceptedDate);
        } catch (ApiClientException $exception) {
            //todo: change order status?
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
