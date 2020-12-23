<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use DateTime;
use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateInterface;

/**
 * Entity that represents an eCurring subscription.
 */
interface SubscriptionInterface
{
    /**
     * Return subscription mandate instance.
     *
     * @return SubscriptionMandateInterface
     */
    public function getMandate(): SubscriptionMandateInterface;
    /**
     * Return a date when a subscription was or will be started.
     *
     * @return DateTime|null Date and time or null if not set.
     */
    public function getStartDate(): ?DateTime;

    /**
     * Return current status of a subscription, one of: active, cancelled, paused, unverified.
     *
     * @return string Subscription status.
     */
    public function getStatus(): string;

    /**
     * Return a date when subscription was or will be cancelled.
     *
     * @return DateTime|null Date and time or null if not set.
     */
    public function getCancelDate(): ?DateTime;

    /**
     * A date when subscription will be resumed.
     *
     * If a subscription is paused and has resume_date field,
     * then it's value will be returned as DateTime object.
     * Otherwise null will be returned.
     *
     * @return DateTime|null Date and time or null if not set.
     */
    public function getResumeDate(): ?DateTime;

    /**
     * Whether subscription was archived.
     *
     * @return bool
     */
    public function getArchived(): bool;

    /**
     * Return the date and time when subscription was created.
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime;

    /**
     * Return the date and time when subscription was updated last time.
     *
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime;
}
