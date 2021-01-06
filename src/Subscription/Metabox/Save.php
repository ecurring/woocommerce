<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Metabox;

use DateTime;
use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;
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
     * @var Actions
     */
    private $actions;

    public function __construct(
        Actions $actions,
        Repository $repository,
        DataBasedSubscriptionFactoryInterface $subscriptionFactory
    ) {
        $this->actions = $actions;
        $this->repository = $repository;
        $this->subscriptionFactory = $subscriptionFactory;
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
                $response = json_decode(
                    $this->actions->pause(
                        $subscriptionId,
                        $this->detectResumeDate()
                    ),
                    true
                );
                $this->updateSubscription($response);
                break;
            case 'resume':
                $response = json_decode($this->actions->resume($subscriptionId), true);
                $this->updateSubscription($response);
                break;
            case 'switch':
                $this->handleSubscriptionSwitch($subscriptionId, $switchDate);
                break;
            case 'cancel':
                $response = json_decode($this->actions->cancel($subscriptionId, $this->detectCancelDate()), true);
                $this->updateSubscription($response);
                break;
        }
    }

    protected function handleSubscriptionSwitch(string $subscriptionId, string $switchDate): void
    {
        $cancel = json_decode($this->actions->cancel($subscriptionId, $switchDate), true);
        $this->updateSubscription($cancel);

        $productId = filter_input(
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

        $create = json_decode(
            $this->actions->create(
                [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => [
                        'customer_id' => $cancel['data']['relationships']['customer']['data']['id'],
                        'subscription_plan_id' => $productId,
                        'mandate_code' => $cancel['data']['attributes']['mandate_code'],
                        'mandate_accepted' => true,
                        'mandate_accepted_date' => $cancel['data']['attributes']['mandate_accepted_date'],
                        'confirmation_sent' => 'true',
                        'subscription_webhook_url' => $subscriptionWebhookUrl,
                        'transaction_webhook_url' => $transactionWebhookUrl,
                        'status' => 'active',
                        "start_date" => $switchDate,
                    ],
                ],
                ]
            ),
            true
        );

        $subscription = $this->subscriptionFactory->createSubscription($create['data']);
        $this->repository->insert($subscription);
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
     * @return string Formatted subscription switch date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSwitchDate(): string
    {
        $switchSubscription = filter_input(
            INPUT_POST,
            'ecurring_switch_subscription',
            FILTER_SANITIZE_STRING
        );

        $switchDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
        if ($switchSubscription === 'specific-date') {
            $switchDate = filter_input(
                INPUT_POST,
                'ecurring_switch_date',
                FILTER_SANITIZE_STRING
            );

            $switchDate = (new DateTime($switchDate))->format('Y-m-d\TH:i:sP');
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
