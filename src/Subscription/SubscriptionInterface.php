<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use DateTime;
use Psr\Http\Message\UriInterface;

/**
 * Entity that represents an eCurring subscription.
 */
interface SubscriptionInterface
{
    /**
     * Return a mandate code.
     *
     * @return string Mandate code or empty string if not set.
     */
    public function getMandateCode(): string;

    /**
     * Whether mandate was accepted.
     *
     * @return bool
     */
    public function getMandateAccepted(): bool;

    /**
     * Return a date when a mandate was accepted, null if not set.
     *
     * @return DateTime|null Date and time or null if not set.
     */
    public function getMandateAcceptedDate(): ?DateTime;

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
     * A link to a subscription activation and mandate acceptation page.
     *
     * @return UriInterface Subscription activation page uri.
     */
    public function getConfirmationPage(): UriInterface;

    /**
     * Whether the confirmation page URI was sent to the customer.
     *
     * @return bool
     */
    public function getConfirmationSent(): bool;

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
