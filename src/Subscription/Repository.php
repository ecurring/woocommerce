<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

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
            if (! $this->subscriptionExistsInDb($subscription->id)) {
                $this->create($subscription);
            }
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
            $this->saveSubscriptionData($postId, $subscription);
        }
    }

    public function update($subscription): void
    {
        $subscriptionPostId = $this->findSubscriptionPostIdBySubscriptionId($subscription->data->id);
        if ($subscriptionPostId === 0) {
            return;
        }

        $this->saveSubscriptionData($subscriptionPostId, $subscription);
    }

    protected function saveSubscriptionData(int $subscriptionPostId, $subscriptionData): void
    {
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_id',
            $subscriptionData->data->id
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_links',
            $subscriptionData->data->links
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_attributes',
            $subscriptionData->data->attributes
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_relationships',
            $subscriptionData->data->relationships
        );
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
        $found = $this->findSubscriptionPostIdBySubscriptionId($subscriptionId);

        return $found !== 0;
    }

    /**
     * @param string $subscriptionId
     *
     * @return int
     */
    protected function findSubscriptionPostIdBySubscriptionId(string $subscriptionId): int
    {
        $found = get_posts(
            [
                'post_type' => 'esubscriptions',
                'numberposts' => 1,
                'post_status' => 'publish',
                'fields' => 'ids',
                'meta_key' => '_ecurring_post_subscription_id',
                'meta_value' => $subscriptionId,
            ]
        );

        return (int) $found[0] ?? 0;
    }
}
