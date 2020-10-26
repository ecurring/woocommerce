<?php

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

    public function init()
    {
        $this->register();
        $this->listColumns();
    }

    protected function register()
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

    protected function listColumns()
    {
        add_filter(
            'manage_esubscriptions_posts_columns',
            function ($columns) {
                unset($columns['date']);

                $columns['title'] = 'Subscription ID';
                $columns['customer'] = 'Customer';
                $columns['product'] = 'Product';
                $columns['start_date'] = 'Start Date';
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
                    case 'customer':
                        $customer = get_post_meta($postId, '_ecurring_post_subscription_customer', true);
                        echo esc_attr($customer->data->attributes->first_name) . ' '
                            . esc_attr($customer->data->attributes->last_name);
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

                        $relationships = get_post_meta(
                            $postId,
                            '_ecurring_post_subscription_relationships',
                            true
                        );
                        $productId = $relationships->{'subscription-plan'}->data->id;
                        echo esc_attr($products[$productId]);
                        break;
                    case 'start_date':
                        $startDate = $attributes->start_date;
                        echo esc_attr((new DateTime($startDate))->format('d-m-Y'));
                        break;
                    case 'status':
                        echo esc_attr(ucfirst($attributes->status));
                        break;
                }
            },
            10,
            2
        );
    }
}
