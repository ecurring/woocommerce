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
     * @var string
     */
    protected $customerId;
    /**
     * @var string
     */
    protected $subscriptionPlanId;
    /**
     * @var array
     */
    protected $meta;

    /**
     * @param string $id Subscription id in the eCurring system.
     * @param string $customerId Subscription customer id in the eCurring system.
     * @param string $subscriptionPlanId Subscription plan id (product id) in the eCurring system.
     * @param SubscriptionMandateInterface $mandate Subscription mandate entity.
     * @param SubscriptionStatusInterface $subscriptionStatus Subscription status entity.
     * @param array $meta Custom meta data.
     */
    public function __construct(
        string $id,
        string $customerId,
        string $subscriptionPlanId,
        SubscriptionMandateInterface $mandate,
        SubscriptionStatusInterface $subscriptionStatus,
        array $meta = []
    ) {

        $this->id = $id;
        $this->mandate = $mandate;
        $this->subscriptionStatus = $subscriptionStatus;
        $this->customerId = $customerId;
        $this->subscriptionPlanId = $subscriptionPlanId;
        $this->meta = $meta;
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
    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getSubscriptionPlanId(): string
    {
        return $this->subscriptionPlanId;
    }

    public function getMeta(): array
    {
        return (array) $this->meta;
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
