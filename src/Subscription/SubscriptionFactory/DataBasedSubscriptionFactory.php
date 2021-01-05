<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionFactory;

use ArrayAccess;
use DateTime;
use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateFactoryInterface;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusFactoryInterface;
use Ecurring\WooEcurring\Subscription\Subscription;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;
use Exception;

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
     * @param array|ArrayAccess $subscriptionData Subscription data that need to be normalized.
     *
     * @return array
     * @throws SubscriptionFactoryException
     */
    protected function normalizeSubscriptionData($subscriptionData): array
    {
        $subscriptionData = (array) $subscriptionData;

        return [
            'mandate_code' => $subscriptionData['mandate_code'] ?? '',
            'confirmation_page' => $subscriptionData['confirmation_page'] ?? '',
            'confirmation_sent' => $subscriptionData['confirmation_sent'] ?? false,
            'mandate_accepted' => $subscriptionData['mandate_accepted'] ?? false,
            'mandate_accepted_date' => $this->createDateFromArrayField(
                $subscriptionData,
                'mandate_accepted_date'
            ),
            'status' => $subscriptionData['status'] ?? '',
            'start_date' => $this->createDateFromArrayField($subscriptionData, 'start_date'),
            'cancel_date' => $this->createDateFromArrayField($subscriptionData, 'cancel_date'),
            'resume_date' => $this->createDateFromArrayField($subscriptionData, 'resume_date'),
            'created_at' => $this->createDateFromArrayField($subscriptionData, 'created_at'),
            'updated_at' => $this->createDateFromArrayField($subscriptionData, 'updated_at'),
            'archived' => $subscriptionData['archived'] ?? false,
        ];
    }

    /**
     * @param array $subscriptionDataArray
     * @param string $dateFieldName
     *
     * @return DateTime|null Created object or null if field not set or equals null.
     *
     * @throws SubscriptionFactoryException If cannot create date from array field.
     */
    protected function createDateFromArrayField(array $subscriptionDataArray, string $dateFieldName): ?DateTime
    {
        try {
            $date = $subscriptionDataArray[$dateFieldName] ?
                new DateTime($subscriptionDataArray[$dateFieldName]) :
                null;
        } catch (Exception $exception) {
            throw new SubscriptionFactoryException(
                sprintf(
                    'Couldn\'t parse date in subscription data. Exception caught when trying to create a DateTime object: %1$s',
                    $exception->getMessage()
                )
            );
        }

        return $date;
    }
}
