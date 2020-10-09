<?php


namespace Ecurring\WooEcurring\Settings;

/**
 * Service able to create, read, update and delete plugin options.
 *
 */
interface SettingsCrudInterface {

	/**
	 * Update existing option or add new.
	 *
	 * @param string $optionName
	 * @param        $optionValue
	 */
	public function updateOption(string $optionName, $optionValue): void;

	/**
	 * Return option if exists, return default otherwise.
	 *
	 * @param string $optionName
	 * @param null   $default
	 *
	 * @return mixed
	 */
	public function getOption(string $optionName, $default = null);

	/**
	 * Delete all plugin settings.
	 */
	public function clearSettings(): void;
}
