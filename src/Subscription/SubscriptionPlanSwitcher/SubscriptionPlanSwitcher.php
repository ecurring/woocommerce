<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher;

use DateTime;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\EcurringException;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherException;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Service able to switch subscription plan using given subscription.
 */
class SubscriptionPlanSwitcher implements SubscriptionPlanSwitcherInterface
{
    /**
     * @var SubscriptionStatusSwitcherInterface
     */
    protected $subscriptionStatusSwitcher;
    /**
     * @var Subscriptions
     */
    protected $subscriptionsApiClient;
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * SubscriptionPlanSwitcher constructor.
     *
     * @param SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher
     * @param Subscriptions $subscriptionsApiClient
     * @param Repository $repository
     */
    public function __construct(
        SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher,
        Subscriptions $subscriptionsApiClient,
        Repository $repository
    ) {

        $this->subscriptionStatusSwitcher = $subscriptionStatusSwitcher;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function switchSubscriptionPlan(
        string $subscriptionId,
        string $newSubscriptionPlanId,
        DateTime $switchDate
    ): SubscriptionInterface {

        try {
            $oldSubscription = $this->repository->getSubscriptionById($subscriptionId);

            if($oldSubscription === null){
                throw new SubscriptionStatusSwitcherException(
                    sprintf(
                        'Cannot find subscription %1$s to change subscription plan.',
                        $subscriptionId
                    )
                );
            }

            $this->subscriptionStatusSwitcher->cancel($oldSubscription->getId(), $switchDate);
            $newSubscription = $this->createNewSubscriptionFromTheOldOne($oldSubscription, $newSubscriptionPlanId, $switchDate);
            $orderId = $this->repository->findSubscriptionOrderIdBySubscriptionId($oldSubscription->getId());
            $this->repository->insert($newSubscription, $orderId);

            return $newSubscription;
        } catch (EcurringException $exception) {
            throw new SubscriptionStatusSwitcherException(
                sprintf(
                    'Failed to switch subscription, exception caught: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * Create a new subscription from an old one.
     *
     * @param SubscriptionInterface $oldSubscription Subscription to use data from.
     * @param string $newSubscriptionPlanId The id of subscription plan for a new subscription.
     * @param DateTime $startDate The date a new subscription to be started from.
     *
     * @return SubscriptionInterface A new subscription.
     * @throws ApiClientException
     * @throws SubscriptionFactoryException
     */
    public function createNewSubscriptionFromTheOldOne(
        SubscriptionInterface $oldSubscription,
        string $newSubscriptionPlanId,
        DateTime $startDate
    ): SubscriptionInterface {
        $mandate = $oldSubscription->getMandate();
        $mandateAcceptedDate = $mandate->getAcceptedDate();

        $subscriptionWebhookUrl = $this->getSubscriptionWebhookUrl();
        $transactionWebhookUrl = $this->getTransactionWebhookUrl();

        $subscriptionAttributes = [
            'mandate_code' => $mandate->getMandateCode(),
            'mandate_accepted' => true,
            'mandate_accepted_date' => $mandateAcceptedDate ?
                $mandateAcceptedDate->format('Y-m-d\TH:i:sP') :
                '',
            'confirmation_sent' => 'true',
            'subscription_webhook_url' => $subscriptionWebhookUrl,
            'transaction_webhook_url' => $transactionWebhookUrl,
            'status' => 'active',
            'start_date' => $startDate->format('Y-m-d\TH:i:sP'),
            'metadata' => json_encode($oldSubscription->getMeta()),
        ];

        return $this->subscriptionsApiClient->create(
            $oldSubscription->getCustomerId(),
            $newSubscriptionPlanId,
            $subscriptionAttributes
        );
    }

    /**
     * @return string
     */
    protected function getSubscriptionWebhookUrl(): string
    {
        return add_query_arg(
            'ecurring-webhook',
            'subscription',
            home_url('/')
        );
    }

    /**
     * @return string
     */
    protected function getTransactionWebhookUrl(): string
    {
        return add_query_arg(
            'ecurring-webhook',
            'transaction',
            home_url('/')
        );
    }
}
