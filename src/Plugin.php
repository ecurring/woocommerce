<?php

namespace eCurring\WooEcurring;

use eCurring\WooEcurring\Subscription\Actions;
use eCurring\WooEcurring\Subscription\Subscription;

class Plugin
{
    /**
     * @var Actions Subscription actions.
     */
    private $actions;

    public function __construct(Actions $actions)
    {
        $this->actions = $actions;
    }

    public function init()
    {
        $this->registerSubscriptionPostType();

        $this->importSubscriptionsJob();

        $this->subscriptionListColumns();
    }

    protected function registerSubscriptionPostType()
    {
        add_action(
            'init',
            function () {

                $args = [
                    'labels' => [
                        'name' => esc_html__('Subscriptions', 'woo-ecurring'),
                        'singular_name' => esc_html__('Subscription', 'woo-ecurring'),
                        'menu_name' => esc_html__('Subscriptions', 'woo-ecurring'),
                    ],
                    'public' => true,
                    'publicly_queryable' => true,
                    'supports' => ['title'],
                ];

                register_post_type('esubscriptions', $args);
            }
        );
    }

    /**
     * Create posts as subscription post type
     */
    protected function createSubscriptions($subscriptions)
    {
        // TODO use WP_Query instead of get_posts
        $subscriptionPosts = get_posts(
            [
                'post_type' => 'esubscriptions',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ]
        );

        $subscriptionIds = [];
        foreach ($subscriptionPosts as $post) {
            $subscriptionIds[] = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);
        }

        foreach ($subscriptions->data as $subscription) {

            if (!$this->orderSubscriptionExist($subscription)) {
                continue;
            }

            if (in_array($subscription->id, $subscriptionIds, true)) {
                continue;
            }

            $postSubscription = new Subscription();
            $postSubscription->create($subscription);
        }
    }

    protected function subscriptionListColumns()
    {
        add_filter(
            'manage_esubscriptions_posts_columns',
            function ($columns) {
                unset($columns['date']);

                $columns['product'] = 'Product';
                $columns['status'] = 'Status';

                return $columns;
            }
        );

        add_action(
            'manage_esubscriptions_posts_custom_column',
            function ($column, $postId) {
                $attributes = get_post_meta(
                    $postId,
                    '_ecurring_post_subscription_attributes',
                    true
                );
                switch ($column) {
                    case 'status':
                        // TODO check if status exists first
                        echo esc_attr(ucfirst($attributes->status));
                        break;
                    case 'product':
                        echo 'product here...';
                        break;
                }

            },
            10,
            2
        );
    }

    /**
     * @param $postId
     * @param $response
     */
    protected function updatePostSubscriptionStatus($postId, $response)
    {
        $attributes = get_post_meta($postId, '_ecurring_post_subscription_attributes', true);
        $attributes->status = $response->data->attributes->status;
        update_post_meta($postId, '_ecurring_post_subscription_attributes', $attributes);
    }

    protected function importSubscriptionsJob()
    {
        add_action(
            'init',
            function () {
                if (wp_doing_ajax()) {
                    return;
                }

                if (get_option('ecurring_import_finished') === '1') {
                    as_unschedule_all_actions('ecurring_import_subscriptions');
                    return;
                }

                if (as_next_scheduled_action('ecurring_import_subscriptions') === false) {
                    as_enqueue_async_action(
                        'ecurring_import_subscriptions',
                        [],
                        'ecurring'
                    );
                }
            }
        );

        add_action(
            'ecurring_import_subscriptions',
            function () {

                $page = get_option('ecurring_subscriptions_page', 1);

                $subscriptions = json_decode($this->actions->import((int)$page));

                $this->createSubscriptions($subscriptions);

                $parts = parse_url($subscriptions->links->next);
                parse_str($parts['query'], $query);
                $nextPage = $query['page']['number'];

                update_option('ecurring_subscriptions_page', $nextPage);

                if (!$nextPage) {
                    update_option('ecurring_import_finished', true);
                }
            }
        );
    }

    /**
     * Check if subscription id exist in orders post meta.
     * @param $subscription
     * @return bool
     */
    protected function orderSubscriptionExist($subscription): bool
    {
        $orders = wc_get_orders(
            [
                'posts_per_page' => -1,
            ]
        );

        foreach ($orders as $order) {
            $subscriptionId = get_post_meta(
                $order->get_id(),
                '_ecurring_subscription_id',
                true
            );

            if ($subscriptionId === $subscription->id) {
                return true;
            }
        }

        return false;
    }
}
