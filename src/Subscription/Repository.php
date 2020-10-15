<?php

namespace Ecurring\WooEcurring\Subscription;

class Repository
{
    /**
     * Create posts as subscription post type
     */
    public function createSubscriptions($subscriptions)
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

            $this->create($subscription);
        }
    }

    public function create($subscription)
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

    public function update($subscription)
    {
        // TODO use WP_Query instead of get_posts
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
