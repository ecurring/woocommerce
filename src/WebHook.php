<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use Ecurring\WooEcurring\Subscription\Repository;
use eCurring_WC_Helper_Api;

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

    public function init(): void
    {
        add_filter(
            'request',
            function ($request) {
                $webhook = filter_input(INPUT_GET, 'ecurring-webhook', FILTER_SANITIZE_STRING);

                if ($webhook === 'transaction') {
                    $response = json_decode(file_get_contents('php://input'));

                    $subscription_id = filter_var(
                        $response->subscription_id,
                        FILTER_SANITIZE_STRING
                    );

                    $subscription = json_decode(
                        $this->api->apiCall(
                            'GET',
                            "https://api.ecurring.com/subscriptions/{$subscription_id}"
                        )
                    );

                    $this->repository->update($subscription);
                }

                if ($webhook === 'subscription') {
                    $response = json_decode(file_get_contents('php://input'));
                    $subscription_id = filter_var(
                        $response->subscription_id,
                        FILTER_SANITIZE_STRING
                    );

                    $subscription = json_decode(
                        $this->api->apiCall(
                            'GET',
                            "https://api.ecurring.com/subscriptions/{$subscription_id}"
                        )
                    );

                    $this->repository->update($subscription);
                }

                return $request;
            }
        );
    }
}
