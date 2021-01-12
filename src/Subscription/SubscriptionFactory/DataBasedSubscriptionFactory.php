<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionFactory;

use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateFactoryInterface;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusFactoryInterface;
use Ecurring\WooEcurring\Subscription\Subscription;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Service for creating subscription instances using mandate and status factories.
 */
class DataBasedSubscriptionFactory implements DataBasedSubscriptionFactoryInterface
{
    /**
     * @var SubscriptionMandateFactoryInterface
     */
    protected $subscriptionMandateFactory;
    /**
     * @var SubscriptionStatusFactoryInterface
     */
    protected $subscriptionStatusFactory;

    /**
     * DataBasedSubscriptionFactory constructor.
     *
     * @param SubscriptionMandateFactoryInterface $subscriptionMandateFactory
     * @param SubscriptionStatusFactoryInterface $subscriptionStatusFactory
     */
    public function __construct(
        SubscriptionMandateFactoryInterface $subscriptionMandateFactory,
        SubscriptionStatusFactoryInterface $subscriptionStatusFactory
    ) {

        $this->subscriptionMandateFactory = $subscriptionMandateFactory;
        $this->subscriptionStatusFactory = $subscriptionStatusFactory;
    }

    /**
     * @inheritDoc
     */
    public function createSubscription(array $subscriptionData): SubscriptionInterface
    {
        $subscriptionMandate = $this->subscriptionMandateFactory
            ->createSubscriptionMandate(
                $subscriptionData['mandate_code'],
                $subscriptionData['confirmation_page'],
                $subscriptionData['confirmation_sent'],
                $subscriptionData['mandate_accepted'],
                $subscriptionData['mandate_accepted_date']
            );

        $subscriptionStatus = $this->subscriptionStatusFactory->createSubscriptionStatus(
            $subscriptionData['status'],
            $subscriptionData['start_date'],
            $subscriptionData['cancel_date'],
            $subscriptionData['resume_date'],
            $subscriptionData['created_at'],
            $subscriptionData['updated_at'],
            $subscriptionData['archived']
        );

        return new Subscription(
            $subscriptionData['subscription_id'],
            $subscriptionData['customer_id'],
            $subscriptionData['subscription_plan_id'],
            $subscriptionMandate,
            $subscriptionStatus
        );
    }
}
