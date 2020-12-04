<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use WP_Post;

class Repository
{
    /**
     * Create posts as subscription post type
     *
     * @return void
     */
    public function createSubscriptions($subscriptions): void
    {

        foreach ($subscriptions->data as $subscription) {
            if (!$this->orderSubscriptionExist($subscription)) {
                continue;
            }

            if ($this->subscriptionExistsInDb($subscription->id)) {
                continue;
            }

            $this->create($subscription);
        }
    }

    public function create($subscription): void
    {
        $postId = wp_insert_post(
            [
                'post_type' => 'esubscriptions',
                'post_title' => $subscription->id,
                'post_status' => 'publish',
            ]
        );

        if ($postId && is_int($postId)) {
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

    public function update($subscription): void
    {
        /**
         * @var WP_Post[]
         *
         * @todo use WP_Query instead of get_posts
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

            if ($postSubscriptionId && $postSubscriptionId === $subscription->data->id) {
                update_post_meta($post->ID, '_ecurring_post_subscription_id', $subscription->data->id);
                update_post_meta(
                    $post->ID,
                    '_ecurring_post_subscription_links',
                    $subscription->data->links
                );
                update_post_meta(
                    $post->ID,
                    '_ecurring_post_subscription_attributes',
                    $subscription->data->attributes
                );
                update_post_meta(
                    $post->ID,
                    '_ecurring_post_subscription_relationships',
                    $subscription->data->relationships
                );
            }
        }
    }

    /**
     * Check if subscription id exist in orders post meta.
     *
     * @param $subscription
     *
     * @return bool
     *
     * @todo: This seems to be very ineffective and potentially may lead to crash of DB has a lot of orders.
     *        Check and rewrite if needed.
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

    /**
     * Check if subscription already saved in the local DB.
     *
     * @param string $subscriptionId The subscription id to check for.
     *
     * @return bool
     */
    protected function subscriptionExistsInDb(string $subscriptionId): bool
    {
        $found = get_posts(
            [
                'post_type' => 'esubscriptions',
                'numberposts' => 1,
                'post_status' => 'publish',
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => '_ecurring_post_subscription_id',
                        'value' => $subscriptionId,
                        'compare' => '=',
                    ],
                ],
            ]
        );

        return count($found) > 0;
    }
}
