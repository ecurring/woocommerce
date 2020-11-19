<?php

use Ecurring\WooEcurring\Settings\SettingsCrudInterface;

defined( 'ABSPATH' ) || die;


return function (string $formAction, SettingsCrudInterface $settings): array {
	return [
		'attributes' => [
			'name' => esc_attr($formAction),
			'type' => 'collection'
		],
		'elements'   => [
			[
				'attributes'       => [
					'name'        => 'api_key',
					'type'        => 'text',
					'placeholder' => _x( 'API key', 'Plugin settings', 'woo-ecurring' ),
					'pattern'     => '^\w{40,}$',
					'value' => $settings->getOption('api_key'),
				],
				'label'            => esc_html_x( 'API key', 'Name of the settings page button', 'woo-ecurring' ),
				'label_attributes' => [ 'for' => 'api_key' ]
			],

			[
				'attributes' => [
					'name'    => 'debug',
					'type'    => 'checkbox',
					'value'   => $settings->getOption('debug') ? 'yes' : ''
				],
				'choices'    => [
					'yes' => esc_html_x(
						'When checked, logs will be created',
						'Plugin settings',
						'woo-ecurring'
					),
				],
				'label'      => esc_html_x( 'Debug Log', 'Plugin settings', 'woo-ecurring' ),
			],
		],
	];
};
