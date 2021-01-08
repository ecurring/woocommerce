<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Metabox;

use DateTime;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
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
     * Save constructor.
     *
     * @param Repository $repository
     * @param DataBasedSubscriptionFactoryInterface $subscriptionFactory
     * @param SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher
     * @param Subscriptions $subscriptionsApiClient
     */
    public function __construct(
        Repository $repository,
        DataBasedSubscriptionFactoryInterface $subscriptionFactory,
        SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher,
        Subscriptions $subscriptionsApiClient
    ) {
        $this->repository = $repository;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscriptionStatusSwitcher = $subscriptionStatusSwitcher;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
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
                $this->subscriptionStatusSwitcher->pause($subscriptionId, $switchDate);
                break;
            case 'resume':
                $this->subscriptionStatusSwitcher->resume($subscriptionId);
                break;
            case 'switch':
                $this->handleSubscriptionSwitch($subscriptionId, $switchDate);
                break;
            case 'cancel':
                $this->subscriptionStatusSwitcher->cancel($subscriptionId, $switchDate);
                break;
        }
    }

    protected function handleSubscriptionSwitch(string $subscriptionId, DateTime $switchDate): void
    {
        $this->subscriptionStatusSwitcher->cancel($subscriptionId, $switchDate);

        $newSubscriptionPlanId = filter_input(
            INPUT_POST,
            'ecurring_subscription_plan',
            FILTER_SANITIZE_STRING
        );

        $subscriptionWebhookUrl = add_query_arg(
            'ecurring-webhook',
            'subscription',
            home_url('/')
        );
        $transactionWebhookUrl = add_query_arg(
            'ecurring-webhook',
            'transaction',
            home_url('/')
        );

        $cancelledSubscription = $this->repository->getSubscriptionById($subscriptionId);

        $mandateCode = $cancelledSubscription->getMandate()->getMandateCode();
        $mandateAcceptedDate = $cancelledSubscription->getMandate()->getAcceptedDate();

        $newSubscriptionData = [
            'type' => 'subscription',
            'attributes' => [
                'customer_id' => $cancelledSubscription->getCustomerId(),
                'subscription_plan_id' => $newSubscriptionPlanId,
                'mandate_code' => $mandateCode,
                'mandate_accepted' => true,
                'mandate_accepted_date' => $mandateAcceptedDate ? $mandateAcceptedDate->format('c') : '',
                'confirmation_sent' => 'true',
                'subscription_webhook_url' => $subscriptionWebhookUrl,
                'transaction_webhook_url' => $transactionWebhookUrl,
                'status' => 'active',
                "start_date" => $switchDate,
            ],
        ];

        $this->subscriptionsApiClient->create(
            [
                'data' => $newSubscriptionData
            ]
        );

        $newSubscription = $this->subscriptionFactory->createSubscription($newSubscriptionData);
        $this->repository->insert($newSubscription);
    }

    /**
     * Get formatted subscription cancel date, if no cancellation date so return an empty string.
     *
     * @return string Formatted subscription cancel date or empty string if no cancel date defined.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectCancelDate(): string
    {
        $cancelSubscription = filter_input(
            INPUT_POST,
            'ecurring_cancel_subscription',
            FILTER_SANITIZE_STRING
        );
        $cancelDate = '';
        if ($cancelSubscription === 'specific-date') {
            $cancelDate = filter_input(
                INPUT_POST,
                'ecurring_cancel_date',
                FILTER_SANITIZE_STRING
            );

            $cancelDate = (new DateTime($cancelDate))->format('Y-m-d\TH:i:sP');
        }

        return $cancelDate;
    }

    /**
     * Detect subscription resume date from posted data.
     *
     * @return string
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectResumeDate(): string
    {
        $pauseSubscription = filter_input(
            INPUT_POST,
            'ecurring_pause_subscription',
            FILTER_SANITIZE_STRING
        );
        $resumeDate = '';
        if ($pauseSubscription === 'specific-date') {
            $resumeDate = filter_input(
                INPUT_POST,
                'ecurring_resume_date',
                FILTER_SANITIZE_STRING
            );

            $resumeDate = (new DateTime($resumeDate))->format('Y-m-d\TH:i:sP');
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
