<?php

namespace eCurring\WooEcurring;

use eCurring\WooEcurring\Subscription\Repository;
use eCurring_WC_Helper_Api;
use WC_Logger;

class WebHook
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(eCurring_WC_Helper_Api $api, Repository $repository)
    {
        $this->api = $api;
        $this->repository = $repository;
    }

    public function init()
    {
        $api = $this->api;
        add_filter(
            'request',
            function ($request) use ($api) {
                $webhook = filter_input(INPUT_GET, 'ecurring-webhook', FILTER_SANITIZE_STRING);

                if ($webhook === 'transaction') {
                    $log = new WC_Logger();
                    $log->add(
                        'transaction',
                        "transaction just received..."
                    );

                    $response = json_decode(file_get_contents('php://input'));
                    $transaction_id = filter_var(
                        $response->transaction_id,
                        FILTER_SANITIZE_STRING
                    );
                    $log->add(
                        'transaction',
                        "transaction {$transaction_id} webhook received in transaction webhook"
                    );

                    $subscription_id = filter_var(
                        $response->subscription_id,
                        FILTER_SANITIZE_STRING
                    );
                    $log->add(
                        'transaction',
                        "subscription {$subscription_id} webhook received in transaction webhook"
                    );

                    $subscription = json_decode(
                        $api->apiCall(
                            'GET',
                            "https://api.ecurring.com/subscriptions/{$subscription_id}"
                        )
                    );
                    $postSubscription = new eCurring\WooEcurring\Subscription\Repository();
                    $postSubscription->update($subscription);

                    $log->add(
                        'transaction',
                        "transaction {$transaction_id} and subscription {$subscription_id} webhook were received"
                    );
                }


                if ($webhook === 'subscription') {
                    $response = json_decode(file_get_contents('php://input'));
                    $subscription_id = filter_var(
                        $response->subscription_id,
                        FILTER_SANITIZE_STRING
                    );

                    $subscription = json_decode(
                        $api->apiCall(
                            'GET',
                            "https://api.ecurring.com/subscriptions/{$subscription_id}"
                        )
                    );

                    $this->repository->update($subscription);

                    $log = new WC_Logger();
                    $log->add(
                        'subscription-webhook',
                        "subscription-webhook {$subscription_id} was received"
                    );
                }

                return $request;
            }
        );
    }
}
