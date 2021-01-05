<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Status;

use DateTime;

/**
 * Represents eCurring subscription status and all it's past and future changes.
 */
class SubscriptionStatus implements SubscriptionStatusInterface
{

    /**
     * @var string
     */
    protected $status;
    /**
     * @var bool
     */
    protected $isArchived;
    /**
     * @var DateTime|null
     */
    protected $startDate;
    /**
     * @var DateTime|null
     */
    protected $cancelDate;
    /**
     * @var DateTime|null
     */
    protected $resumedDate;
    /**
     * @var DateTime|null
     */
    protected $createdAt;
    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    public function __construct(
        string $status,
        bool $isArchived,
        DateTime $startDate = null,
        DateTime $cancelDate = null,
        DateTime $resumedDate = null,
        DateTime $createdAt = null,
        DateTime $updatedAt = null
    ) {

        $this->status = $status;
        $this->isArchived = $isArchived;
        $this->startDate = $startDate;
        $this->cancelDate = $cancelDate;
        $this->resumedDate = $resumedDate;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentStatus(): string
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     */
    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    /**
     * @inheritDoc
     */
    public function getCancelDate(): ?DateTime
    {
        return $this->cancelDate;
    }

    /**
     * @inheritDoc
     */
    public function getResumeDate(): ?DateTime
    {
        return $this->resumedDate;
    }

    /**
     * @inheritDoc
     */
    public function getArchived(): bool
    {
        return $this->isArchived;
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
