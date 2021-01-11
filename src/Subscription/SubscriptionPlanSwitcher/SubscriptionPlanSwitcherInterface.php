<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher;

use DateTime;
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
     * @param string $newSubscriptionPlanId Id of the subscription plan to switch to.
     * @param DateTime $switchDate The date when the switch should happen.
     *
     * @return SubscriptionInterface A new subscription instance with given subscription plan.
     */
    public function switchSubscriptionPlan(
        SubscriptionInterface $oldSubscription,
        string $newSubscriptionPlanId,
        DateTime $switchDate
    ): SubscriptionInterface;
}
