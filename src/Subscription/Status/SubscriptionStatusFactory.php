<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Status;

use DateTime;

/**
 * Service able to create subscription status instance.
 */
class SubscriptionStatusFactory implements SubscriptionStatusFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createSubscriptionStatus(
        string $currentStatus,
        DateTime $startDate = null,
        DateTime $cancelDate = null,
        DateTime $resumeDate = null,
        DateTime $createdAt = null,
        DateTime $updatedAt = null,
        bool $archived = false
    ): SubscriptionStatusInterface {
        return new SubscriptionStatus(
            $currentStatus,
            $archived,
            $startDate,
            $cancelDate,
            $resumeDate,
            $createdAt,
            $updatedAt
        );
    }
}
