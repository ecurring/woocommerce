<?php

namespace eCurring\WooEcurring\Subscription;

class Repository
{
    public function create($subscription)
    {
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
}
