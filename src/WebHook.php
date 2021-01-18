<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use Ecurring\WooEcurring\Api\Subscriptions as SubscriptionsApi;
use Ecurring\WooEcurring\Subscription\Repository;

class WebHook
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var SubscriptionsApi
     */
    private $subscriptionsApi;

    public function __construct(SubscriptionsApi $subscriptionsApi, Repository $repository)
    {
        $this->subscriptionsApi = $subscriptionsApi;
        $this->repository = $repository;
    }

    public function init(): void
    {
        add_filter(
            'request',
            function ($request) {
                $webhook = filter_input(INPUT_GET, 'ecurring-webhook', FILTER_SANITIZE_STRING);

                if (in_array($webhook, ['transaction', 'subscription'], true)) {
                    $response = json_decode(file_get_contents('php://input'));

                    $subscriptionId = filter_var(
                        $response->subscription_id,
                        FILTER_SANITIZE_STRING
                    );

                    $subscription = $this->subscriptionsApi->getSubscriptionById($subscriptionId);

                    $this->repository->update($subscription);
                }

                return $request;
            }
        );
    }
}
