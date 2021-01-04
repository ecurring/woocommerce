<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionFactory;

use ArrayAccess;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Service able to create a subscription instance from data.
 */
interface DataBasedSubscriptionFactoryInterface
{
    /**
     * Create a new subscription from given data array.
     *
     * @param string $subscriptionId Subscription id in the eCurring system.
     * @param array|ArrayAccess $subscriptionData Subscription attributes formatted as map.
     *
     * @return SubscriptionInterface Created subscription instance.
     *
     * @throws SubscriptionFactoryException If cannot create a new subscription instance.
     */
    public function createSubscription(string $subscriptionId, $subscriptionData): SubscriptionInterface;
}
