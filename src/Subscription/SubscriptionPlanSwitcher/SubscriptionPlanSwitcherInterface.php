<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher;

use DateTime;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherException;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Service able to switch subscription plan using given subscription instance.
 */
interface SubscriptionPlanSwitcherInterface
{

    /**
     * Switch subscription plan on the remote API and return a new subscription instance.
     *
     * @param string $subscriptionId The id of the subscription that should be changed.
     * @param string $newSubscriptionPlanId Id of the subscription plan to switch to.
     * @param DateTime $switchDate The date when the switch should happen.
     *
     * @return SubscriptionInterface A new subscription instance with given subscription plan.
     *
     * @throws SubscriptionStatusSwitcherException If failed to switch subscription.
     */
    public function switchSubscriptionPlan(
        string $subscriptionId,
        string $newSubscriptionPlanId,
        DateTime $switchDate
    ): SubscriptionInterface;
}
