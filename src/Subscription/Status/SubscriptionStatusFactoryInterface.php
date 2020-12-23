<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Status;

use DateTime;

/**
 * Service able to create subscription status instance.
 */
interface SubscriptionStatusFactoryInterface
{
    /**
     * @param string $currentStatus
     * @param DateTime|null $startDate
     * @param DateTime|null $cancelDate
     * @param DateTime|null $resumeDate
     * @param DateTime|null $createdAt
     * @param DateTime|null $updatedAt
     * @param bool $archived
     *
     * @return SubscriptionStatusInterface A new subscription status instance.
     */
    public function createSubscriptionStatus(
        string $currentStatus,
        DateTime $startDate = null,
        DateTime $cancelDate = null,
        DateTime $resumeDate = null,
        DateTime $createdAt = null,
        DateTime $updatedAt = null,
        bool $archived = false
    ): SubscriptionStatusInterface;
}
