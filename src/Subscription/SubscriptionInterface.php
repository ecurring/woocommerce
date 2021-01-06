<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateInterface;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusInterface;

/**
 * Entity that represents an eCurring subscription.
 */
interface SubscriptionInterface
{
    /**
     * Return subscription id in the eCurring.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Return eCurring subscription customer id.
     *
     * @return string
     */
    public function getCustomerId(): string;

    /**
     * Return ecurring subscription plan (product) id.
     *
     * @return string
     */
    public function getSubscriptionPlanId(): string;

    /**
     * Return subscription mandate instance.
     *
     * @return SubscriptionMandateInterface
     */
    public function getMandate(): SubscriptionMandateInterface;

    /**
     * Return object aware of subscription status, it's previous and future changes.
     *
     * @return SubscriptionStatusInterface
     */
    public function getStatus(): SubscriptionStatusInterface;
}
