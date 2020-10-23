<?php

namespace Ecurring\WooEcurring\Customer;

use DateTime;
use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;
use eCurring_WC_Helper_Api;
use Exception;

class MyAccount
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    /**
     * @var Actions
     */
    private $actions;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var Subscriptions
     */
    private $subscriptions;

    public function __construct(
        eCurring_WC_Helper_Api $api,
        Actions $actions,
        Repository $repository,
        Subscriptions $subscriptions
    ) {
        $this->api = $api;
        $this->actions = $actions;
        $this->repository = $repository;
        $this->subscriptions = $subscriptions;
    }

    public function init()
    {
        add_filter(
            'woocommerce_account_menu_items',
            function ($items) {
                $newItems = [];
                $newItems['ecurring-subscriptions'] = __('Subscriptions', 'woo-ecurring');
                $position = array_search('orders', array_keys($items), true) + 1;

                $finalItems = array_slice($items, 0, $position, true);
                $finalItems += $newItems;
                $finalItems += array_slice($items, $position, count($items) - $position, true);

                return $finalItems;
            }
        );

        add_action(
            'woocommerce_account_ecurring-subscriptions_endpoint',
            function () {
                $this->subscriptions->display();
            }
        );

        add_filter(
            'query_vars',
            function ($vars) {
                $vars[] = 'ecurring-subscriptions';
                return $vars;
            },
            0
        );

        add_filter(
            'the_title',
            function ($title) {
                global $wp_query;
                if (isset($wp_query->query_vars['ecurring-subscriptions']) && in_the_loop()) {
                    return __('Subscriptions', 'woo-ecurring');
                }

                return $title;
            },
            10,
            2
        );

        add_action(
            'wp_ajax_ecurring_customer_subscriptions',
            function () {
                $this->doSubscriptionAction($this->actions);
            }
        );
        add_action(
            'wp_ajax_nopriv_ecurring_customer_subscriptions',
            function () {
                $this->doSubscriptionAction($this->actions);
            }
        );
    }

    /**
     * @param Actions $actions
     * @throws Exception
     */
    protected function doSubscriptionAction(
        Actions $actions
    ): void {
        $subscriptionType = filter_input(
            INPUT_POST,
            'ecurring_subscription_type',
            FILTER_SANITIZE_STRING
        );

        $subscriptionId = filter_input(
            INPUT_POST,
            'ecurring_subscription_id',
            FILTER_SANITIZE_STRING
        );

        $pauseSubscription = filter_input(
            INPUT_POST,
            'ecurring_pause_subscription',
            FILTER_SANITIZE_STRING
        );
        $resumeDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
        if ($pauseSubscription === 'specific-date') {
            $resumeDate = filter_input(
                INPUT_POST,
                'ecurring_resume_date',
                FILTER_SANITIZE_STRING
            );

            $resumeDate = (new DateTime($resumeDate))->format('Y-m-d\TH:i:sP');
        }

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

        $cancelSubscription = filter_input(
            INPUT_POST,
            'ecurring_cancel_subscription',
            FILTER_SANITIZE_STRING
        );
        $cancelDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
        if ($cancelSubscription === 'specific-date') {
            $cancelDate = filter_input(
                INPUT_POST,
                'ecurring_cancel_date',
                FILTER_SANITIZE_STRING
            );

            $cancelDate = (new DateTime($cancelDate))->format('Y-m-d\TH:i:sP');
        }

        switch ($subscriptionType) {
            case 'pause':
                $response = json_decode(
                    $actions->pause(
                        $subscriptionId,
                        $resumeDate
                    )
                );
                $this->updatePostSubscription($response);
                break;
            case 'resume':
                $response = json_decode($actions->resume($subscriptionId));
                $this->updatePostSubscription($response);
                break;
            case 'switch':
                $cancel = json_decode($actions->cancel($subscriptionId, $switchDate));
                $this->updatePostSubscription($cancel);

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

                $response = json_decode(
                    $actions->create(
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
                    )
                );

                $postSubscription = new Repository();
                $postSubscription->create($response->data);
                break;
            case 'cancel':
                $response = json_decode(
                    $actions->cancel($subscriptionId, $cancelDate)
                );
                $this->updatePostSubscription($response);
                break;
        }

        wp_die();
    }

    /**
     * @param $response
     */
    protected function updatePostSubscription($response)
    {
        $subscriptionPosts = get_posts(
            [
                'post_type' => 'esubscriptions',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ]
        );

        foreach ($subscriptionPosts as $post) {
            $postSubscriptionId = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);

            if ($postSubscriptionId && $postSubscriptionId === $response->data->id) {
                update_post_meta($post->ID, '_ecurring_post_subscription_id', $response->data->id);
                update_post_meta(
                    $post->ID,
                    '_ecurring_post_subscription_links',
                    $response->data->links
                );
                update_post_meta(
                    $post->ID,
                    '_ecurring_post_subscription_attributes',
                    $response->data->attributes
                );
                update_post_meta(
                    $post->ID,
                    '_ecurring_post_subscription_relationships',
                    $response->data->relationships
                );
            }
        }
    }
}
