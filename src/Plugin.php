<?php

namespace eCurring\WooEcurring;

use eCurring\WooEcurring\Subscription\Actions;
use eCurring\WooEcurring\Subscription\Import;
use eCurring_WC_Helper_Api;

class Plugin
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    public function __construct(eCurring_WC_Helper_Api $api)
    {
        $this->api = $api;;
    }

    public function init()
    {
        $this->registerSubscriptionPostType();

        //$this->importSubscriptions();

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

    protected function importSubscriptions()
    {
        $import = new Import($this->api);

        $subscriptions = json_decode($import->import());

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
}
