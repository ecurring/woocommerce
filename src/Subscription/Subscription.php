<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateInterface;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusInterface;

/**
 * Represents an eCurring subscription.
 */
class Subscription implements SubscriptionInterface
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var SubscriptionMandateInterface
     */
    protected $mandate;
    /**
     * @var SubscriptionStatusInterface
     */
    protected $subscriptionStatus;

    /**
     * @param string $id Subscription id in the eCurring system.
     * @param SubscriptionMandateInterface $mandate Subscription mandate entity.
     * @param SubscriptionStatusInterface $subscriptionStatus Subscription status entity.
     */
    public function __construct(
        string $id,
        SubscriptionMandateInterface $mandate,
        SubscriptionStatusInterface $subscriptionStatus
    ) {

        $this->id = $id;
        $this->mandate = $mandate;
        $this->subscriptionStatus = $subscriptionStatus;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getMandate(): SubscriptionMandateInterface
    {
        return $this->mandate;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): SubscriptionStatusInterface
    {
        return $this->subscriptionStatus;
    }
}
