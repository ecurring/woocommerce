<?php

namespace Ecurring\WooEcurring\Subscription\Metabox;

use DateTime;

use Ecurring\WooEcurring\Api\Subscriptions as SubscriptionsApi;
use Ecurring\WooEcurring\Subscription\Repository;

class Save
{
    /**
     * @var SubscriptionsApi
     */
    private $subscriptionsApi;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(SubscriptionsApi $subscriptionsApi, Repository $repository)
    {
        $this->subscriptionsApi = $subscriptionsApi;
        $this->repository = $repository;
    }

    public function save($postId)
    {
        $subscriptionType = filter_input(
            INPUT_POST,
            'ecurring_subscription',
            FILTER_SANITIZE_STRING
        );

        $subscriptionId = filter_input(
            INPUT_POST,
            'ecurring_subscription_id',
            FILTER_SANITIZE_STRING
        );

        if ($this->subscriptionNotAllowed($subscriptionType, $subscriptionId)) {
            return;
        }

        $resumeDate = $this->setResumeDate();
        $cancelDate = $this->setCancelDate();
        $switchDate = $this->setSwitchDate();

        switch ($subscriptionType) {
            case 'pause':
                $response = $this->subscriptionsApi->pause($subscriptionId, $resumeDate);
                $this->updateSubscriptionAttributes($postId, $response);
                break;
            case 'resume':
                $response = $this->subscriptionsApi->resume($subscriptionId);
                $this->updateSubscriptionAttributes($postId, $response);
                break;
            case 'switch':
                $cancel = $this->subscriptionsApi->cancel($subscriptionId, $switchDate);
                $this->updateSubscriptionAttributes($postId, $cancel);

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

                $create = $this->subscriptionsApi->create(
                    [
                        'data' => [
                            'type' => 'subscription',
                            'attributes' => [
                                'customer_id' => $cancel->data->relationships->customer->data->id,
                                'subscription_plan_id' => $productId,
                                'mandate_code' => $cancel->data->attributes->mandate_code,
                                'mandate_accepted' => true,
                                'mandate_accepted_date' => $cancel->data->attributes->mandate_accepted_date,
                                'confirmation_sent' => 'true',
                                'subscription_webhook_url' => $subscriptionWebhookUrl,
                                'transaction_webhook_url' => $transactionWebhookUrl,
                                'status' => 'active',
                                "start_date" => $switchDate,
                            ],
                        ],
                    ]
                );
                $this->repository->create($create->data);
                break;
            case 'cancel':
                $response = $this->subscriptionsApi->cancel($subscriptionId, $cancelDate);
                $this->updateSubscriptionAttributes($postId, $response);
                break;
        }
    }

    /**
     * @param $postId
     * @param $response
     */
    protected function updateSubscriptionAttributes($postId, $response)
    {
        update_post_meta(
            $postId,
            '_ecurring_post_subscription_attributes',
            $response->data->attributes
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function setResumeDate(): string
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
     * @return string
     * @throws \Exception
     */
    protected function setCancelDate(): string
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
     * @return string
     * @throws \Exception
     */
    protected function setSwitchDate(): string
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
     * @param $subscriptionType
     * @param $subscriptionId
     * @return bool
     */
    protected function subscriptionNotAllowed($subscriptionType, $subscriptionId): bool
    {
        return !$subscriptionType
            || !in_array($subscriptionType, ['pause', 'resume', 'switch', 'cancel'], true)
            || !$subscriptionId;
    }
}
