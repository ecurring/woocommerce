<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use DateTime;
use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;
use eCurring_WC_Helper_Api;
use Exception;
use WP_Post;

use function in_the_loop;
use function add_action;
use function add_filter;
use function filter_input;
use function json_decode;
use function get_post_meta;
use function update_post_meta;
use function home_url;
use function add_query_arg;
use function get_posts;
use function wp_die;

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

    public function init(): void
    {
        add_action(
            'woocommerce_account_ecurring-subscriptions_endpoint',
            function () {
                $this->subscriptions->display();
            }
        );

        add_filter(
            'query_vars',
            static function ($vars) {
                $vars[] = 'ecurring-subscriptions';
                return $vars;
            },
            0
        );

        add_filter(
            'the_title',
            static function ($title) {
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

        $switchDate = $this->detectSubscriptionSwitchDate();

        switch ($subscriptionType) {
            case 'pause':
                $response = json_decode(
                    $actions->pause(
                        $subscriptionId,
                        $this->detectSubscriptionResumeDate()
                    )
                );
                $this->updatePostSubscription($response);
                break;
            case 'resume':
                $response = json_decode($actions->resume($subscriptionId));
                $this->updatePostSubscription($response);
                break;
            case 'switch':
                $this->doSubscriptionSwitch($actions, $subscriptionId, $switchDate);
                break;
            case 'cancel':
                $response = json_decode(
                    $actions->cancel($subscriptionId, $this->detectSubscriptionCancelDate())
                );
                $this->updatePostSubscription($response);
                break;
        }

        wp_die();
    }

    protected function doSubscriptionSwitch(Actions $actions, string $subscriptionId, string $switchDate): void
    {
        $cancel = json_decode($actions->cancel($subscriptionId, $switchDate));
        $this->updatePostSubscription($cancel);

        $productId = filter_input(
            INPUT_POST,
            'ecurring_subscription_plan',
            FILTER_SANITIZE_STRING
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
                            'subscription_webhook_url' => $this->buildSubscriptionWebhookUrl(),
                            'transaction_webhook_url' => $this->buildTransactionWebhookUrl(),
                            'status' => 'active',
                            "start_date" => $switchDate,
                        ],
                    ],
                ]
            )
        );

        $postSubscription = new Repository();
        $postSubscription->create($response->data);
    }

    /**
     * Build and return url to be used for a subscription webhook call.
     *
     * @return string
     */
    protected function buildSubscriptionWebhookUrl(): string
    {
        return add_query_arg(
            'ecurring-webhook',
            'subscription',
            home_url('/')
        );
    }

    /**
     * Build and return url to be used for a transaction webhook call.
     *
     * @return string
     */
    protected function buildTransactionWebhookUrl(): string
    {
        return add_query_arg(
            'ecurring-webhook',
            'transaction',
            home_url('/')
        );
    }

    /**
     * Get formatted subscription switch date from posted data.
     *
     * @return string Formatted subscription switch date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionSwitchDate(): string
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
     * Get formatted subscription resume date from posted data.
     *
     * @return string Formatted subscription cancel date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionResumeDate(): string
    {
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

        return $resumeDate;
    }

    /**
     * Get formatted subscription cancel date from posted data.
     *
     * @return string Formatted subscription cancel date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionCancelDate(): string
    {
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

        return $cancelDate;
    }

    /**
     * @param $response
     *
     * @return void
     */
    protected function updatePostSubscription($response): void
    {
        /**
         * @var array<WP_Post>
         */
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
