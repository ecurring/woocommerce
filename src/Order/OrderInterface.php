<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Order;

use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Represents order in Woocommerce from the plugin's point of view.
 */
interface OrderInterface
{
    /**
     * Return internal (Woocommerce) order id.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Return list of subscriptions purchased with this order.
     *
     * @return iterable<SubscriptionInterface>
     */
    public function getSubscriptions(): iterable;

    /**
     * Return the Woocommerce id of the order customer.
     *
     * @return int
     */
    public function getLocalCustomerId(): int;
}
