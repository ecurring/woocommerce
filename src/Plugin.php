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

        add_action(
            'add_meta_boxes',
            function () {
                add_meta_box(
                    'ecurring_subscription',
                    'Subscription',
                    function ($post) {
                        $attributes = get_post_meta(
                            $post->ID,
                            '_ecurring_post_subscription_attributes',
                            true
                        );
                        $status = $attributes->status;
                        ?>
                        <label for="ecurring_subscription_status">Status</label>
                        <select name="ecurring_subscription_status" class="postbox">
                            <option value="active" <?php selected($status, 'active'); ?>>Active
                            </option>
                            <option value="cancelled" <?php selected($status, 'cancelled'); ?>>
                                Canceled
                            </option>
                            <option value="paused" <?php selected($status, 'paused'); ?>>Paused
                            </option>
                            <option value="unverified" <?php selected($status, 'unverified'); ?>>
                                Unverified
                            </option>
                        </select>
                    <?php },
                    'esubscriptions'
                );
            }
        );

        add_action(
            'save_post',
            function ($postId) {

                $status = filter_input(
                    INPUT_POST,
                    'ecurring_subscription_status',
                    FILTER_SANITIZE_STRING
                );

                if ($status && in_array(
                        $status,
                        ['active', 'cancelled', 'paused', 'unverified'],
                        true
                    )) {

                    $subscriptionId = get_post_meta($postId, '_ecurring_post_subscription_id', true);
                    $actions = new Actions($this->api, $subscriptionId);

                    switch ($status) {
                        case 'cancelled':
                            $result = $actions->cancel();

                            // TODO grab status from response and update 'ecurring_subscription_status'
                    }
                }
            }
        );
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
}
