<?php

namespace Ecurring\WooEcurring;


/**
 * Check if environment is suitable for this plugin to work.
 */
class EnvironmentChecker {

	/**
	 * @return bool Whether WooCommerce plugin is active.
	 */
	public function isMollieActive()
	{
		return is_plugin_active('mollie/mollie-payments-for-woocommerce.php');
	}
}
