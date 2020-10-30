<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use Ecurring\WooEcurring\EnvironmentChecker\EnvironmentCheckerInterface;

/**
 * Check if environment is suitable for this plugin to work.
 */
class EnvironmentChecker implements EnvironmentCheckerInterface
{
    const MOLLIE_MINIMUM_VERSION = '6.0.0';

    /**
     * @return bool Whether Mollie plugin is active.
     */
    public function isMollieActive(): bool
    {
        return is_plugin_active('mollie-payments-for-woocommerce/mollie-payments-for-woocommerce.php');
    }

    /**
     * @return bool Whether current Mollie version is allowed
     */
    public function isMollieMinimalVersion(): bool
    {
        if (!defined('M4W_FILE')) {
            return false;
        }

        $molliePluginData = get_plugin_data(M4W_FILE);
        $currentMollieVersion = $molliePluginData['Version'];
        return version_compare(
            $currentMollieVersion,
            self::MOLLIE_MINIMUM_VERSION,
            '>='
        );
    }

    /**
     * @inheritDoc
     */
    public function checkEnvironment(): bool
    {
        return $this->isMollieActive() && $this->isMollieMinimalVersion();
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): iterable
    {
        return [];
    }
}
