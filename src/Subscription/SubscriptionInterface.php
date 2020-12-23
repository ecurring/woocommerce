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
     * Return subscription mandate instance.
     *
     * @return SubscriptionMandateInterface
     */
    public function getMandate(): SubscriptionMandateInterface;

    /**
     * Return subscription status interface.
     *
     * @return SubscriptionStatusInterface
     */
    public function getStatus(): SubscriptionStatusInterface;
}
