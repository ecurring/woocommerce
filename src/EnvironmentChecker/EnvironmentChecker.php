<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EnvironmentChecker;

use WooCommerce;

/**
 * Check if environment is suitable for this plugin to work.
 */
class EnvironmentChecker implements EnvironmentCheckerInterface
{
    const MOLLIE_MINIMUM_VERSION = '6.0.0';

    /**
     * @var string Minimum required PHP version.
     */
    protected $minPhpVersion;

    /**
     * @param string $minPhpVersion The minimum PHP version this plugin able to work with.
     */
    public function __construct(string $minPhpVersion)
    {
        $this->minPhpVersion = $minPhpVersion;
    }

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
        return $this->checkPhpVersion() &&
            $this->checkWoocommerceIsActive() &&
            $this->isMollieActive() &&
            $this->isMollieMinimalVersion();
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): iterable
    {
        return [];
    }

    /**
     * Check whether current PHP version met plugin requirements.
     *
     * @return bool
     */
    protected function checkPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, $this->minPhpVersion, '>=');
    }

    /**
     * Check whether WooCommerce is active.
     *
     * @return bool
     */
    protected function checkWoocommerceIsActive(): bool
    {
        return class_exists(WooCommerce::class);
    }

}
