<?php

namespace eCurring\WooEcurring;

use eCurring\WooEcurring\Subscription\Actions;
use WC_Logger;

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

            if (in_array($subscription->id, $subscriptionIds, true)) {
                continue;
            }

            $postId = wp_insert_post(
                [
                    'post_type' => 'esubscriptions',
                    'post_title' => $subscription->id,
                    'post_status' => 'publish',
                ]
            );

            if ($postId) {
                add_post_meta($postId, '_ecurring_post_subscription_id', $subscription->id);
                add_post_meta($postId, '_ecurring_post_subscription_links', $subscription->links);
                add_post_meta(
                    $postId,
                    '_ecurring_post_subscription_attributes',
                    $subscription->attributes
                );
                add_post_meta(
                    $postId,
                    '_ecurring_post_subscription_relationships',
                    $subscription->relationships
                );
            }
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
                    as_schedule_recurring_action(
                        strtotime('now'),
                        10,
                        'ecurring_import_subscriptions'
                    );
                }
            }
        );

        add_action(
            'ecurring_import_subscriptions',
            function () {

                $log = new WC_Logger();

                $page = get_option('ecurring_subscriptions_page', 1);
                $log->add('ecurring-import-subscriptions', 'page: ' . $page);

                $subscriptions = json_decode($this->actions->import((int)$page));

                $parts = parse_url($subscriptions->links->next);
                parse_str($parts['query'], $query);
                $nextPage = $query['page']['number'];

                $this->createSubscriptions($subscriptions);

                update_option('ecurring_subscriptions_page', $nextPage);
                $log->add('ecurring-import-subscriptions', 'next: ' . $nextPage);

                if (!$nextPage) {
                    update_option('ecurring_import_finished', true);

                    $log->add('ecurring-import-subscriptions', 'unscheduled');
                }
            }
        );
    }
}