<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\StatusSwitcher;

use DateTime;

/**
 * Service able to change subscription status at both local and remote API sides.
 */
interface SubscriptionStatusSwitcherInterface
{
    /**
     * Activate a new subscription (subscription should exist already).
     *
     * @param string $subscriptionId
     * @param DateTime $mandateAcceptedDate
     *
     * @throws SubscriptionStatusSwitcherException If failed to activate subscription.
     */
    public function activate(string $subscriptionId, DateTime $mandateAcceptedDate): void;

    /**
     * Pause a subscription.
     *
     * @param string $subscriptionId
     *
     * @throws SubscriptionStatusSwitcherException If failed to pause subscription.
     */
    public function pause(string $subscriptionId): void;

    /**
     * Resume paused subscription.
     *
     * @param string $subscriptionId
     *
     * @throws SubscriptionStatusSwitcherException If failed to resume subscription.
     */
    public function resume(string $subscriptionId): void;

    /**
     * Cancel a subscription.
     *
     * @param string $subscriptionId
     *
     * @throws SubscriptionStatusSwitcherException If failed to cancel subscription.
     */
    public function cancel(string $subscriptionId): void;
}
