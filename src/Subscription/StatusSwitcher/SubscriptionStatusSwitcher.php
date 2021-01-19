<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\StatusSwitcher;

use DateTime;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\EcurringException;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use eCurring_WC_Plugin;

/**
 * Service for switching subscription status using eCurring API and update its local status.
 */
class SubscriptionStatusSwitcher implements SubscriptionStatusSwitcherInterface
{
    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var Subscriptions
     */
    protected $subscriptionsApiClient;

    /**
     * SubscriptionStatusSwitcher constructor.
     *
     * @param Subscriptions $subscriptionsApiClient To send subscription-related API requests.
     * @param Repository $repository To persist subscription instance.
     */
    public function __construct(
        Subscriptions $subscriptionsApiClient,
        Repository $repository
    ) {

        $this->repository = $repository;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
    }

    /**
     * @inheritDoc
     */
    public function activate(string $subscriptionId, DateTime $mandateAcceptedDate): void
    {
        try {
            $this->subscriptionsApiClient->activate($subscriptionId, $mandateAcceptedDate);
            $this->updateSubscriptionFromApi($subscriptionId);

            eCurring_WC_Plugin::debug(
                sprintf('Subscription %1$s was activated.', $subscriptionId)
            );
        } catch (EcurringException $exception) {
            throw new SubscriptionStatusSwitcherException(
                sprintf(
                    'Failed to activate subscription. Exception was caught when tried: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function pause(string $subscriptionId, DateTime $resumeDate = null): void
    {
        try {
            $response = $this->subscriptionsApiClient->pause($subscriptionId, $resumeDate);
            $this->updateSubscriptionFromApi($subscriptionId);

            eCurring_WC_Plugin::debug(
                sprintf('Subscription %1$s was paused.', $subscriptionId)
            );

            eCurring_WC_Plugin::debug(
                sprintf('API response: %1$s', print_r($response, true))
            );
        } catch (EcurringException $exception) {
            throw new SubscriptionStatusSwitcherException(
                sprintf(
                    'Failed to pause subscription. Exception was caught when tried: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function resume(string $subscriptionId): void
    {
        try {
            $this->subscriptionsApiClient->resume($subscriptionId);
            $this->updateSubscriptionFromApi($subscriptionId);

            eCurring_WC_Plugin::debug(
                sprintf('Subscription %1$s was resumed.', $subscriptionId)
            );
        } catch (EcurringException $exception) {
            throw new SubscriptionStatusSwitcherException(
                sprintf(
                    'Failed to resume subscription. Exception was caught when tried: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $subscriptionId, DateTime $cancelDate = null): void
    {
        try {
            $cancelDate = $cancelDate ? $cancelDate->format('c') : false;
            $this->subscriptionsApiClient->cancel($subscriptionId, $cancelDate);
            $this->updateSubscriptionFromApi($subscriptionId);

            eCurring_WC_Plugin::debug(
                sprintf('Subscription %1$s was cancelled.', $subscriptionId)
            );
        } catch (EcurringException $exception) {
            throw new SubscriptionStatusSwitcherException(
                sprintf(
                    'Failed to activate subscription. Exception was caught when tried: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * Get subscription data from the eCurring API and save it locally.
     *
     * @param string $subscriptionId
     *
     * @throws SubscriptionFactoryException
     * @throws ApiClientException
     */
    protected function updateSubscriptionFromApi(string $subscriptionId): void
    {
        $subscription = $this->subscriptionsApiClient->getSubscriptionById($subscriptionId);
        $this->repository->update($subscription);
    }
}
