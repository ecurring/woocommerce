<?php

defined('ABSPATH') || die;

return [
	'attributes' => [
		'name' => 'mollie-subscriptions-settings-form',
		'type' => 'form'
	],
	'elements' => [
		[
			'attributes' => [
				'name' => 'api_key',
				'type' => 'text',
				'placeholder'       => _x('API key', 'Plugin settings', 'woo-ecurring'),
				'pattern'     => '^\w{40,}$',
				'default' => get_option('woo-ecurring_live_api_key') //todo: replace this.
			],
			'label'             => esc_html_x('API key', 'Name of the settings page button', 'woo-ecurring'),
			'label_attributes'  => [ 'for' => 'api_key' ]
		],

		[
			'attributes' => [
				'name' => 'debug',
				'type' => 'checkbox',
				'default' => 'yes',
			],
			'choices' => [
				'enabled' => esc_html_x(
					'When checked, logs will be created',
					'Plugin settings',
					'woo-ecurring'
				),
			],
			'label'     => esc_html_x('Debug Log', 'Plugin settings','woo-ecurring'),
		],

	],
];
