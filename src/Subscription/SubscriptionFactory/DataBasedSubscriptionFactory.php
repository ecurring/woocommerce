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
    public function createSubscription(string $subscriptionId, $subscriptionData): SubscriptionInterface
    {
        $normalizedData = $this->normalizeSubscriptionData($subscriptionData);

        $subscriptionMandate = $this->subscriptionMandateFactory
            ->createSubscriptionMandate(
                $normalizedData['mandate_code'],
                $normalizedData['confirmation_page'],
                $normalizedData['confirmation_sent'],
                $normalizedData['mandate_accepted'],
                $normalizedData['mandate_accepted_date']
            );

        $subscriptionStatus = $this->subscriptionStatusFactory->createSubscriptionStatus(
            $normalizedData['status'],
            $normalizedData['start_date'],
            $normalizedData['cancel_date'],
            $normalizedData['resume_date'],
            $normalizedData['created_at'],
            $normalizedData['updated_at'],
            $normalizedData['archived']
        );

        return new Subscription(
            $subscriptionId,
            $subscriptionMandate,
            $subscriptionStatus
        );
    }

    /**
     * @param $subscriptionData
     *
     * @return array
     */
    protected function normalizeSubscriptionData($subscriptionData): array
    {
        return $subscriptionData;
    }
}
