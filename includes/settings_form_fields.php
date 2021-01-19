<?php

declare(strict_types=1);

use Ecurring\WooEcurring\Settings\SettingsCrudInterface;

defined('ABSPATH') || die;

return static function (string $formAction, SettingsCrudInterface $settings): array {
    return [
        'attributes' => [
            'name' => esc_attr($formAction),
            'type' => 'collection',
        ],
        'elements' => [
            [
                'attributes' => [
                    'name' => 'api_key',
                    'type' => 'text',
                    'placeholder' => _x('API key', 'Plugin settings', 'woo-ecurring'),
                    'pattern' => '^\w{40,}$',
                    'value' => $settings->getOption('api_key'),
                ],
                'label' => esc_html_x('API key', 'Name of the settings page button', 'woo-ecurring'),
                'label_attributes' => [ 'for' => 'api_key' ],
            ],

            [
                'attributes' => [
                    'name' => 'debug',
                    'type' => 'checkbox',
                    'value' => $settings->getOption('debug') === 'no' ? '' : 'yes',
                ],
                'choices' => [
                    'yes' => esc_html_x(
                        'When checked, logs will be created',
                        'Plugin settings',
                        'woo-ecurring'
                    ),
                ],
                'label' => esc_html_x('Debug Log', 'Plugin settings', 'woo-ecurring'),
            ],
            [
                'attributes' => [
                    'name' => 'ecurring_customer_subscription_pause',
                    'type' => 'checkbox',
                    'value' => $settings->getOption(
                        'ecurring_customer_subscription_pause'
                    ) === 'no' ? '' : 'yes',
                ],
                'choices' => [
                    'yes' => esc_html_x(
                        'When checked, customers able to pause subscription in Woocommerce account',
                        'Plugin settings',
                        'woo-ecurring'
                    ),
                ],
                'label' => esc_html_x('Pause subscription', 'Plugin settings', 'woo-ecurring'),
            ],
            [
                'attributes' => [
                    'name' => 'ecurring_customer_subscription_switch',
                    'type' => 'checkbox',
                    'value' => $settings->getOption(
                        'ecurring_customer_subscription_switch'
                    ) === 'no' ? '' : 'yes',
                ],
                'choices' => [
                    'yes' => esc_html_x(
                        'When checked, customers able to switch subscription plan in Woocommerce account',
                        'Plugin settings',
                        'woo-ecurring'
                    ),
                ],
                'label' => esc_html_x('Switch subscription', 'Plugin settings', 'woo-ecurring'),
            ],
            [
                'attributes' => [
                    'name' => 'ecurring_customer_subscription_cancel',
                    'type' => 'checkbox',
                    'value' => $settings->getOption(
                        'ecurring_customer_subscription_cancel'
                    ) === 'no' ? '' : 'yes',
                ],
                'choices' => [
                    'yes' => esc_html_x(
                        'When checked, customers able to cancel subscription in Woocommerce account',
                        'Plugin settings',
                        'woo-ecurring'
                    ),
                ],
                'label' => esc_html_x('Cancel subscription', 'Plugin settings', 'woo-ecurring'),
            ],
        ],
    ];
};
