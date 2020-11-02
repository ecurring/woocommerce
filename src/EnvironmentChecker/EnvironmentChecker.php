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
     * @var string
     */
    protected $minWoocommerceVersion;

    /**
     * @param string $minPhpVersion The minimum required PHP version.
     * @param string $minWoocommerceVersion The minimum required WC version.
     */
    public function __construct(string $minPhpVersion, string $minWoocommerceVersion)
    {
        $this->minPhpVersion = $minPhpVersion;
        $this->minWoocommerceVersion = $minWoocommerceVersion;
    }

    /**
     * @return bool Whether Mollie plugin is active.
     */
    public function checkMollieIsActive(): bool
    {
        if (!defined('M4W_FILE')) {
            return false;
        }

        if(! function_exists('plugin_basename')) {
            require_once ABSPATH . WPINC . '/plugin.php';
        }

        $molliePluginBasename = plugin_basename(M4W_FILE);

        return is_plugin_active($molliePluginBasename);
    }

    /**
     * @return bool Whether current Mollie version is allowed
     */
    public function isMollieMinimalVersion(): bool
    {
        if (!defined('M4W_FILE')) {
            return false;
        }

        if(! function_exists('get_plugin_data')){
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
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
            $this->checkMollieIsActive() &&
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

    /**
     * Check whether current WooCommerce version met plugin requirements.
     *
     * @return bool
     */
    protected function checkWoocommerceVersion(): bool
    {
        if(! defined('WC_VERSION')){
            return false;
        }

        return version_compare(WC_VERSION, $this->minWoocommerceVersion, '>=');
    }
}
