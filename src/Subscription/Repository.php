<?php

namespace Ecurring\WooEcurring\Subscription;

use Ecurring\WooEcurring\Api\Customers as CustomersApi;
use WP_Query;

class Repository
{
    /**
     * @var CustomersApi
     */
    private $customers;

    public function __construct(CustomersApi $customers)
    {
        $this->customers = $customers;
    }

    /**
     * Create posts as subscription post type
     * @param object[] $subscriptions
     */
    public function createSubscriptions($subscriptions)
    {
        $subscriptionIds = $this->getSubscriptionIds();

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
            $customerDetails = $this->customers->getCustomerById(
                $subscription->relationships->customer->data->id
            );
            add_post_meta($postId, '_ecurring_post_subscription_customer', $customerDetails);

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
        $posts = $this->getAllSubscriptionPosts();

        foreach ($posts as $post) {
            $postSubscriptionId = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);

            if ($postSubscriptionId && $postSubscriptionId === $subscription->data->id) {
                $customerDetails = $this->customers->getCustomerById(
                    $subscription->relationships->customer->data->id
                );
                update_post_meta($post->ID, '_ecurring_post_subscription_customer', $customerDetails);

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

    /**
     * @return array
     */
    protected function getSubscriptionIds(): array
    {
        $query = new WP_Query(
            [
                [
                    'post_type' => 'esubscriptions',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                ],
            ]
        );

        $subscriptionIds = [];
        foreach ($query->posts as $post) {
            $subscriptionIds[] = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);
        }

        return $subscriptionIds;
    }

    /**
     * @return array
     */
    protected function getAllSubscriptionPosts()
    {
        $query = new WP_Query(
            [
                [
                    'post_type' => 'esubscriptions',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                ],
            ]
        );

        return $query->posts;
    }
}
