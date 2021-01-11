<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher;

use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Service able to switch subscription plan using given subscription instance.
 */
interface SubscriptionPlanSwitcherInterface
{

    /**
     * Switch subscription plan on the remote API and return a new subscription instance.
     *
     * @param SubscriptionInterface $oldSubscription Subscription instance with
     *          current subscription plan.
     * @param string $newPlanId Id of the subscription plan to switch to.
     *
     * @return SubscriptionInterface A new subscription instance with given subscription plan.
     */
    public function switchSubscriptionPlan(
        SubscriptionInterface $oldSubscription,
        string $newPlanId
    ): SubscriptionInterface;
}
