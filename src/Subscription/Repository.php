<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use Ecurring\WooEcurring\Api\Customers;
use eCurring_WC_Helper_Api;
use eCurring_WC_Helper_Settings;
use eCurring_WC_Plugin;

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
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Preparing to save subscription %1$s.',
                    $subscription->id
                )
            );

            if ($this->subscriptionExistsInDb($subscription->id)) {
                eCurring_WC_Plugin::debug(
                    sprintf(
                        'Subscription %1$s already exists in local database, ' .
                        'saving will be skipped.',
                        $subscription->id
                    )
                );
                continue;
            }
            if (! $this->orderWithSubscriptionExists($subscription->id)) {
                eCurring_WC_Plugin::debug(
                    sprintf(
                        'Order not found for the subscription %1$s, saving will be skipped.',
                        $subscription->id
                    )
                );
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
            $customer = $this->getCustomerApi();
            $customerDetails = $customer->getCustomerById(
                $subscription->relationships->customer->data->id
            );

            $this->saveSubscriptionData($postId, $subscription, $customerDetails);

            eCurring_WC_Plugin::debug(
                sprintf(
                    'Subscription %1$s successfully saved as post %2$d',
                    $subscription->id,
                    $postId
                )
            );
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

    protected function saveSubscriptionData(int $subscriptionPostId, $subscriptionData, $customerDetails = null): void
    {
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_id',
            $subscriptionData->id
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_links',
            $subscriptionData->links
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_attributes',
            $subscriptionData->attributes
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_relationships',
            $subscriptionData->relationships
        );

        if ($customerDetails !== null) {
            update_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_customer',
                $customerDetails
            );
        }
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
        /** @var int[] $found */
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

        return $found[0] ?? 0;
    }

    /**
     * Check if order with the given subscription id exists.
     *
     * @param string $subscriptionId The id of the subscription to look for.
     *
     * @return bool True if order containing given subscription id exists, false otherwise.
     */
    protected function orderWithSubscriptionExists(string $subscriptionId): bool
    {
        $foundOrderId = $this->findSubscriptionOrderIdBySubscriptionId($subscriptionId);

        return $foundOrderId !== 0;
    }

    /**
     * Return an id of the order containing given subscription, return 0 if not found.
     *
     * @param string $subscriptionId Subscription id to find order with.
     *
     * @return int Found order id.
     */
    protected function findSubscriptionOrderIdBySubscriptionId(string $subscriptionId): int
    {
        $addSubscriptionIdMetaSupport = function (array $wpQueryArgs, array $wcOrdersQueryArgs) use ($subscriptionId): array {
            if (! empty($wcOrdersQueryArgs['_ecurring_subscription_id'])) {
                $wpQueryArgs['meta_query'][] = [
                    'key' => '_ecurring_subscription_id',
                    'value' => $subscriptionId,
                ];
            }

            return $wpQueryArgs;
        };

        add_filter(
            'woocommerce_order_data_store_cpt_get_orders_query',
            $addSubscriptionIdMetaSupport,
            10,
            2
        );

        /** @var array $foundIds */
        $foundIds = wc_get_orders(
            [
                'limit' => 1,
                'return' => 'ids',
                '_ecurring_subscription_id' => $subscriptionId,
            ]
        );

        remove_filter(
            'woocommerce_order_data_store_cpt_get_orders_query',
            $addSubscriptionIdMetaSupport
        );

        return $foundIds[0] ?? 0;
    }

    /**
     * @return Customers
     */
    protected function getCustomerApi(): Customers
    {
        $settingsHelper = new eCurring_WC_Helper_Settings();
        $api = new eCurring_WC_Helper_Api($settingsHelper);
        $customer = new Customers($api);
        return $customer;
    }
}
