<?php

namespace Ecurring\WooEcurring\Settings;

/**
 * Service able to create, read, update and delete options
 *
 * Currently, we are saving all options as single array to avoid problems with cleaning.
 *
 * Another reason is to avoid need to migrate settings from previous plugin version. We are using the same option name
 * for options array storage so it would work after update.
 *
 * In the future, it would be a good idea to find a better approach.
 */
class SettingsCrud implements SettingsCrudInterface {

	protected const OPTIONS_STORAGE_KEY = 'ecurring_wc_gateway_ecurring_settings';

	/**
	 * @var array
	 */
	private $options;

	public function __construct()
	{
		$this->options = get_option(self::OPTIONS_STORAGE_KEY);

	}

	/**
	 * @inheritDoc
	 */
	public function updateOption( string $optionName, $optionValue ): void {
		$this->options[$optionName] = $optionValue;
		$this->persist();
	}

	/**
	 * @inheritDoc
	 */
	public function getOption( string $optionName, $default = null ) {
		return $this->options[$optionName] ?? $default;
	}

	/**
	 * @inheritDoc
	 */
	public function clearSettings(): void {
		delete_option(self::OPTIONS_STORAGE_KEY);
	}

	/**
	 * @inheritDoc
	 */
	public function persist(): void
	{
		update_option(self::OPTIONS_STORAGE_KEY, $this->options);
	}
}
