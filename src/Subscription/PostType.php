<?php

namespace Ecurring\WooEcurring\Subscription;

class PostType
{
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
                        // TODO check if status exists first
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
}
