<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Mandate;

use DateTime;

/**
 * Service creating subscription mandate instances.
 */
class SubscriptionMandateFactory implements SubscriptionMandateFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createSubscriptionMandate(string $code, string $confirmationPageUrl, bool $confirmationSent, bool $accepted, DateTime $acceptedDate = null): SubscriptionMandateInterface
    {
        return new SubscriptionMandate(
            $code,
            $accepted,
            $confirmationPageUrl,
            $confirmationSent,
            $acceptedDate
        );
    }
}
