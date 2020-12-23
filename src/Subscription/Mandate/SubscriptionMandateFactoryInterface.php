<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Mandate;

use DateTime;

/**
 * Service able to create new subscription mandate instances.
 */
interface SubscriptionMandateFactoryInterface
{
    /**
     * @param string $code Mandate code.
     * @param string $confirmationPageUrl URL of the page where mandate can be accepted.
     * @param bool $confirmationSent Whether confirmation with
     *                               confirmation page url was sent to the customer.
     * @param bool $accepted Whether mandate was accepted.
     * @param DateTime|null $acceptedDate The date when mandate was accepted or null if it wasn't.
     *
     * @return SubscriptionMandateInterface
     */
    public function createSubscriptionMandate(
        string $code,
        string $confirmationPageUrl,
        bool $confirmationSent,
        bool $accepted,
        DateTime $acceptedDate = null
    ): SubscriptionMandateInterface;
}
