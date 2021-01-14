<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Mandate;

use DateTime;

/**
 * Represents eCurring subscription mandate entity.
 */
class SubscriptionMandate implements SubscriptionMandateInterface
{
    /**
     * @var string
     */
    protected $mandateCode;
    /**
     * @var bool
     */
    protected $isAccepted;
    /**
     * @var string
     */
    protected $confirmationPageUrl;
    /**
     * @var bool
     */
    protected $confirmationSent;
    /**
     * @var DateTime|null
     */
    protected $acceptedDate;

    /**
     * SubscriptionMandate constructor.
     *
     * @param string $mandateCode
     * @param bool $isAccepted
     * @param string $confirmationPageUrl
     * @param bool $confirmationSent
     * @param DateTime|null $acceptedDate
     */
    public function __construct(
        string $mandateCode,
        bool $isAccepted,
        string $confirmationPageUrl,
        bool $confirmationSent,
        DateTime $acceptedDate = null
    ) {
        $this->mandateCode = $mandateCode;
        $this->isAccepted = $isAccepted;
        $this->confirmationPageUrl = $confirmationPageUrl;
        $this->confirmationSent = $confirmationSent;
        $this->acceptedDate = $acceptedDate;
    }

    /**
     * @inheritDoc
     */
    public function getMandateCode(): string
    {
        return $this->mandateCode;
    }

    /**
     * @inheritDoc
     */
    public function getAccepted(): bool
    {
        return $this->isAccepted;
    }

    /**
     * @inheritDoc
     */
    public function getAcceptedDate(): ?DateTime
    {
        return $this->acceptedDate;
    }

    /**
     * @inheritDoc
     */
    public function getConfirmationPageUrl(): string
    {
        return $this->confirmationPageUrl;
    }

    /**
     * @inheritDoc
     */
    public function getConfirmationSent(): bool
    {
        return $this->confirmationSent;
    }
}
