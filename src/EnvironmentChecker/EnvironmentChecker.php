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
     * @var array List of the error messages if environment is not ok.
     */
    protected $errors;

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
        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function checkEnvironment(): bool
    {
        return $this->checkPhpVersion() &&
            $this->checkWoocommerceIsActive() &&
            $this->checkMollieIsActive() &&
            $this->checkMollieVersion();
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): iterable
    {
        return $this->errors;
    }

    /**
     * Check whether current PHP version met plugin requirements.
     *
     * @return bool
     */
    protected function checkPhpVersion(): bool
    {
        $phpVersionIsOk = version_compare(PHP_VERSION, $this->minPhpVersion, '>=');

        if(! $phpVersionIsOk)
        {
            $this->errors[] = __(
                'Mollie Subscriptions plugin is disabled. Please, update your PHP version first.',
                'woo-ecurring'
            );
        }

        return $phpVersionIsOk;
    }

    /**
     * Check whether WooCommerce is active.
     *
     * @return bool
     */
    protected function checkWoocommerceIsActive(): bool
    {
        $wcIsActive = class_exists(WooCommerce::class);

        if(! $wcIsActive){
            $this->errors[] = __(
                'Mollie Subscriptions plugin is inactive. Please, activate Woocommerce plugin first.',
                'woo-ecurring'
            );
        }

        return $wcIsActive;
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

        $wcVersionIsOk = version_compare(WC_VERSION, $this->minWoocommerceVersion, '>=');

        if(! $wcVersionIsOk) {
            $this->errors[] = __(
                'Mollie Subscriptions plugin is inactive. Please, update WooCommerce plugin first',
                'woo-ecurring'
            );
        }

        return $wcVersionIsOk;
    }

    /**
     * @return bool Whether Mollie plugin is active.
     */
    public function checkMollieIsActive(): bool
    {
        $isMollieActive = false;

        if(defined('M4W_FILE')){

            if(! function_exists('plugin_basename')) {
                require_once ABSPATH . WPINC . '/plugin.php';
            }

            $molliePluginBasename = plugin_basename(M4W_FILE);

            $isMollieActive = is_plugin_active($molliePluginBasename);
        }

        if(! $isMollieActive){
            $molliePluginPageUrl = http_build_query([
                'tab'=> 'plugin-information',
                'plugin' => 'mollie-payments-for-woocommerce'
            ]);
            $molliePluginPageUrl = admin_url('plugin-install.php?' . $molliePluginPageUrl);

            $this->errors[] = sprintf(
            /* translators: %1$s is replaced with Mollie plugin installation page url. */
                __(
                    '<strong>Mollie Subscriptions plugin is inactive.</strong> Please, install and activate <a href="%1$s">Mollie Payments for WooCommerce</a> plugin first.',
                    'woo-ecurring'
                ),
                esc_url($molliePluginPageUrl)
            );
        }

        return $isMollieActive;
    }

    /**
     * @return bool Whether current Mollie version is allowed
     */
    public function checkMollieVersion(): bool
    {
        $isMollieVersionOk = false;

        if(defined('M4W_FILE')){

            if(! function_exists('get_plugin_data')){
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $molliePluginData = get_plugin_data(M4W_FILE);
            $currentMollieVersion = $molliePluginData['Version'];

            $isMollieVersionOk = version_compare(
                $currentMollieVersion,
                self::MOLLIE_MINIMUM_VERSION,
                '>='
            );
        }

        if(! $isMollieVersionOk){

            $molliePluginPageUrl = http_build_query([
                'tab'=> 'plugin-information',
                'plugin' => 'mollie-payments-for-woocommerce'
            ]);
            $molliePluginPageUrl = admin_url('plugin-install.php?' . $molliePluginPageUrl);

            $this->errors[] = $mollieIsNotMinimalVersionMessage = sprintf(
            /* translators: %1$s is replaced with Mollie plugin installation page url. */
                __(
                    '<strong>Mollie Subscriptions plugin is inactive.</strong> Please, update <a href="%1$s">Mollie Payments for WooCommerce</a> plugin first.',
                    'woo-ecurring'
                ),
                $molliePluginPageUrl
            );
        }

        return $isMollieVersionOk;
    }
}
