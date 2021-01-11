<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Metabox;

use DateTime;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherException;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher\SubscriptionPlanSwitcher;
use eCurring_WC_Plugin;
use Exception;

class Save
{
    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var DataBasedSubscriptionFactoryInterface
     */
    protected $subscriptionFactory;
    /**
     * @var SubscriptionStatusSwitcherInterface
     */
    protected $subscriptionStatusSwitcher;
    /**
     * @var Subscriptions
     */
    protected $subscriptionsApiClient;
    /**
     * @var SubscriptionPlanSwitcher
     */
    protected $subscriptionPlanSwitcher;

    /**
     * Save constructor.
     *
     * @param Repository $repository
     * @param DataBasedSubscriptionFactoryInterface $subscriptionFactory
     * @param SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher
     * @param SubscriptionPlanSwitcher $subscriptionPlanSwitcher
     * @param Subscriptions $subscriptionsApiClient
     */
    public function __construct(
        Repository $repository,
        DataBasedSubscriptionFactoryInterface $subscriptionFactory,
        SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher,
        SubscriptionPlanSwitcher $subscriptionPlanSwitcher,
        Subscriptions $subscriptionsApiClient
    ) {
        $this->repository = $repository;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscriptionStatusSwitcher = $subscriptionStatusSwitcher;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
        $this->subscriptionPlanSwitcher = $subscriptionPlanSwitcher;
    }

    /**
     * @return void
     */
    public function save()
    {
        $subscriptionType = $subscriptionType = filter_input(
            INPUT_POST,
            'ecurring_subscription',
            FILTER_SANITIZE_STRING
        );

        $subscriptionId = filter_input(
            INPUT_POST,
            'ecurring_subscription_id',
            FILTER_SANITIZE_STRING
        );

        $switchDate = $this->detectSwitchDate();

        if (
            !$subscriptionType || !in_array(
                $subscriptionType,
                ['pause', 'resume', 'switch', 'cancel'],
                true
            ) || !$subscriptionId
        ) {
            return;
        }

        switch ($subscriptionType) {
            case 'pause':
                $this->subscriptionStatusSwitcher->pause($subscriptionId, $this->detectResumeDate());
                break;
            case 'resume':
                $this->subscriptionStatusSwitcher->resume($subscriptionId);
                break;
            case 'switch':
                $this->handleSubscriptionSwitch(
                    $subscriptionId,
                    $this->getPostedNewSubscriptionPlanId(),
                    $switchDate
                );
                break;
            case 'cancel':
                $this->subscriptionStatusSwitcher->cancel($subscriptionId, $this->detectCancelDate());
                break;
        }
    }

    /**
     * @param string $subscriptionId
     * @param string $newSubscriptionPlanId
     * @param DateTime $switchDate
     *
     * @throws SubscriptionStatusSwitcherException
     */
    protected function handleSubscriptionSwitch(string $subscriptionId, string $newSubscriptionPlanId, DateTime $switchDate): void
    {
        $currentSubscription = $this->repository->getSubscriptionById($subscriptionId);

        $this->subscriptionPlanSwitcher->switchSubscriptionPlan($currentSubscription, $newSubscriptionPlanId, $switchDate);
    }

    /**
     * @return string
     */
    protected function getPostedNewSubscriptionPlanId(): string
    {
        return (string) filter_input(
            INPUT_POST,
            'ecurring_subscription_plan',
            FILTER_SANITIZE_STRING
        );
    }

    /**
     * Get formatted subscription cancel date, if no cancellation date so return an empty string.
     *
     * @return DateTime Subscription cancel date, using current date if no cancel date defined.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectCancelDate(): DateTime
    {
        $cancelSubscription = filter_input(
            INPUT_POST,
            'ecurring_cancel_subscription',
            FILTER_SANITIZE_STRING
        );
        $cancelDate = new DateTime();
        if ($cancelSubscription === 'specific-date') {
            $cancelDate = filter_input(
                INPUT_POST,
                'ecurring_cancel_date',
                FILTER_SANITIZE_STRING
            );

            $cancelDate = new DateTime($cancelDate);
        }

        return $cancelDate;
    }

    /**
     * Detect subscription resume date from posted data.
     *
     * @return DateTime|null
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectResumeDate(): ?DateTime
    {
        $pauseSubscription = filter_input(
            INPUT_POST,
            'ecurring_pause_subscription',
            FILTER_SANITIZE_STRING
        );
        $resumeDate = null;
        if ($pauseSubscription === 'specific-date') {
            $resumeDate = filter_input(
                INPUT_POST,
                'ecurring_resume_date',
                FILTER_SANITIZE_STRING
            );

            $resumeDate = (new DateTime($resumeDate));
        }

        return $resumeDate;
    }

    /**
     * Get formatted subscription switch date from posted data.
     *
     * @return DateTime Subscription switch date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSwitchDate(): DateTime
    {
        $switchSubscription = filter_input(
            INPUT_POST,
            'ecurring_switch_subscription',
            FILTER_SANITIZE_STRING
        );

        $switchDate = (new DateTime('now'));
        if ($switchSubscription === 'specific-date') {
            $switchDate = filter_input(
                INPUT_POST,
                'ecurring_switch_date',
                FILTER_SANITIZE_STRING
            );

            $switchDate = (new DateTime($switchDate));
        }

        return $switchDate;
    }

    /**
     * @param $response
     *
     * @return void
     */
    protected function updateSubscription($response): void
    {

        if (! isset($response['data'])) {
            eCurring_WC_Plugin::debug(
                'Couldn\'t update subscription from the API response. No data found in the response.'
            );

            return;
        }

        $subscriptionData = $response['data'] ?? [];

        try {
            $subscription = $this->subscriptionFactory->createSubscription($subscriptionData);
            $this->repository->update($subscription);
        } catch (SubscriptionFactoryException $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Couldn\'t create subscription from the API response. Exception caught: %1$s',
                    $exception->getMessage()
                )
            );
        }
    }
}
