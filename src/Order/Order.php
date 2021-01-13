<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Order;

use WC_Order;

/**
 * A shop order that may contain subscriptions.
 */
class Order implements OrderInterface
{

    /**
     * @var WC_Order
     */
    protected $wcOrder;
    /**
     * @var iterable
     */
    protected $subscriptions;

    /**
     * @param WC_Order $wcOrder Woocommerce order.
     * @param iterable $subscriptions
     */
    public function __construct(WC_Order $wcOrder, iterable $subscriptions)
    {

        $this->wcOrder = $wcOrder;
        $this->subscriptions = $subscriptions;
    }

    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return $this->wcOrder->get_id();
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptions(): iterable
    {
        return $this->subscriptions;
    }

    /**
     * @inheritDoc
     */
    public function getLocalCustomerId(): int
    {
        return $this->wcOrder->get_customer_id();
    }
}
