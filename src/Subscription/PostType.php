<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use DateTime;
use Ecurring\WooEcurring\Api\SubscriptionPlans;
use eCurring_WC_Helper_Api;

class PostType
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    public function __construct(eCurring_WC_Helper_Api $api)
    {
        $this->api = $api;
    }

    public function init(): void
    {
        $this->register();
        $this->listColumns();

        add_filter(
            'post_row_actions',
            static function ($actions) {
                if (get_post_type() === 'esubscriptions') {
                    unset($actions['edit']);
                    unset($actions['view']);
                    unset($actions['trash']);
                    unset($actions['inline hide-if-no-js']);
                }
                return $actions;
            },
            10,
            1
        );
    }

    protected function register(): void
    {
        add_action(
            'init',
            static function () {

                $args = [
                    'labels' => [
                        'name' => esc_html__('Subscriptions', 'woo-ecurring'),
                        'singular_name' => esc_html__('Subscription', 'woo-ecurring'),
                        'menu_name' => esc_html__('Subscriptions', 'woo-ecurring'),
                        'add_new_item' => __('Add New Subscription', 'woo-ecurring'),
                        'new_item' => __('New Subscription', 'woo-ecurring'),
                        'edit_item' => __('Edit Subscription', 'woo-ecurring'),
                        'view_item' => __('View Subscription', 'woo-ecurring'),
                        'all_items' => __('All Subscriptions', 'woo-ecurring'),
                        'search_items' => __('Search Subscriptions', 'woo-ecurring'),
                        'not_found' => __('No subscriptions found.', 'woo-ecurring'),
                        'not_found_in_trash' => __(
                            'No subscriptions found in Trash.',
                            'woo-ecurring'
                        ),
                        'items_list_navigation' => _x(
                            'Subscriptions list navigation',
                            'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4',
                            'woo-ecurring'
                        ),
                        'items_list' => _x(
                            'Subscriptions list',
                            'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4',
                            'woo-ecurring'
                        ),
                    ],
                    'public' => false,
                    'show_ui' => true,
                    'publicly_queryable' => true,
                    'supports' => ['title'],
                    'capabilities' => [
                        'create_posts' => false,
                    ],
                    'map_meta_cap' => true,
                ];

                register_post_type('esubscriptions', $args);
            }
        );
    }

    protected function listColumns(): void
    {
        $this->registerPostTypeTableColumns();
        $this->registerPostTypeTableColumnsContent();
    }

    protected function registerPostTypeTableColumns(): void
    {
        add_filter(
            'manage_esubscriptions_posts_columns',
            static function ($columns) {
                unset($columns['date']);

                $columns['title'] = 'Subscription ID';
                $columns['customer'] = 'Customer';
                $columns['product'] = 'Product';
                $columns['start_date'] = 'Start Date';
                $columns['status'] = 'Status';

                return $columns;
            }
        );
    }

    protected function registerPostTypeTableColumnsContent(): void
    {
        add_action(
            'manage_esubscriptions_posts_custom_column',
            function ($column, $postId) {
                switch ($column) {
                    case 'customer':
                        $customer = get_post_meta($postId, '_ecurring_post_subscription_customer', true);
                        echo esc_attr($customer->data->attributes->first_name ?? '')  . ' '
                            . esc_attr($customer->data->attributes->last_name ?? '') ;
                        break;
                    case 'product':
                        $subscriptionPlans = (new SubscriptionPlans(
                            $this->api
                        ))->getSubscriptionPlans();

                        if (!isset($subscriptionPlans->data)) {
                            break;
                        }

                        $products = [];
                        foreach ($subscriptionPlans->data as $product) {
                            $products[$product->id] = $product->attributes->name ?: '';
                        }

                        $productId = get_post_meta($postId, '_ecurring_post_subscription_plan_id', true);
                        echo esc_attr($products[$productId] ?? '');
                        break;
                    case 'start_date':
                        $startDate = get_post_meta($postId, '_ecurring_post_subscription_start_date', true);
                        $startDate = (new DateTime($startDate))->format('Y-m-d\TH:i:sP');
                        echo esc_attr($startDate);
                        break;
                    case 'status':
                        echo esc_attr(ucfirst(get_post_meta($postId, '_ecurring_post_subscription_status', true)));
                        break;
                }
            },
            10,
            2
        );
    }
}
