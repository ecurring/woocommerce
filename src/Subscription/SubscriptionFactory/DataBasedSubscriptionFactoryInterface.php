<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionFactory;

use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Service able to create a subscription instance from data.
 */
interface DataBasedSubscriptionFactoryInterface
{
    /**
     * Create a new subscription from given data array.
     *
     * @param array $subscriptionData Subscription data formatted
     *    as {@link https://docs.ecurring.com/subscriptions/get/ eCurring subscription}
     *
     * @return SubscriptionInterface Created subscription instance.
     *
     * @throws SubscriptionFactoryException If cannot create a new subscription instance.
     */
    public function createSubscription($subscriptionData): SubscriptionInterface;
}
