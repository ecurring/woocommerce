<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EnvironmentChecker;

use Dhii\Package\Version\StringVersionFactoryInterface;
use eCurring_WC_Plugin;
use Exception;

/**
 * Check if environment is suitable for this plugin to work.
 */
class EnvironmentChecker implements EnvironmentCheckerInterface
{
    /**
     * @var string Minimum required PHP version.
     */
    protected $minPhpVersion;

    /**
     * @var array<string> List of the error messages if environment is not ok.
     */
    protected $errors;

    /**
     * @var string
     */
    protected $minWoocommerceVersion;

    /**
     * @var string
     */
    protected $minMollieVersion;
    /**
     * @var StringVersionFactoryInterface
     */
    protected $versionFactory;

    /**
     * @param string               $minPhpVersion            The minimum required PHP version.
     * @param string               $minWoocommerceVersion    The minimum required WC version.
     * @param string               $minMollieVersion         The minimum required Mollie Payments version.
     * @param StringVersionFactoryInterface $versionFactory Factory to create Version instance,
     *                                                      used to normalize version string.
     */
    public function __construct(
        string $minPhpVersion,
        string $minWoocommerceVersion,
        string $minMollieVersion,
        StringVersionFactoryInterface $versionFactory
    ) {
        $this->minPhpVersion = $minPhpVersion;
        $this->minWoocommerceVersion = $minWoocommerceVersion;
        $this->errors = [];
        $this->minMollieVersion = $minMollieVersion;
        $this->versionFactory = $versionFactory;
    }

    /**
     * @inheritDoc
     */
    public function checkEnvironment(): bool
    {
        return $this->checkPhpVersion() &&
            $this->checkJsonExtension() &&
            $this->checkWoocommerceIsActive() &&
            $this->checkWoocommerceVersion() &&
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
        $phpVersionIsOk = $this->checkVersion(phpversion(), $this->minPhpVersion);

        if (! $phpVersionIsOk) {
            $this->errors[] = __(
                'WooCommerce eCurring gateway plugin is disabled. Please, update your PHP first.',
                'woo-ecurring'
            );
        }

        return $phpVersionIsOk;
    }

    /**
     * Check whether json extension is loaded.
     *
     * @return bool
     */
    protected function checkJsonExtension(): bool
    {
        $jsonExtensionLoaded = extension_loaded('json');

        if (! $jsonExtensionLoaded) {
            $this->errors[] = esc_html__(
                'WooCommerce eCurring gateway plugin requires the JSON extension for PHP. Enable it in your server or ask your webhoster to enable it for you.',
                'woo-ecurring'
            );
        }

        return $jsonExtensionLoaded;
    }

    /**
     * Check whether WooCommerce is active.
     *
     * @return bool
     */
    protected function checkWoocommerceIsActive(): bool
    {
        $wcIsActive = in_array(
            'woocommerce/woocommerce.php',
            apply_filters('active_plugins', get_option('active_plugins'))
        );

        if (! $wcIsActive) {
            $woocommercePluginPageUrl = $this->buildInstallPluginPageLink('woocommerce');

            $this->errors[] = sprintf(
                /* translators: %1$s is replaced with WooCommerce plugin installation page url. */
                __(
                    '<strong>WooCommerce eCurring gateway plugin is inactive.</strong> Please, install and activate <a href="%1$s">WooCommerce</a> plugin first.',
                    'woo-ecurring'
                ),
                $woocommercePluginPageUrl
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
        if (! defined('WC_VERSION')) {
            return false;
        }

        $wcVersionIsOk = $this->checkVersion(WC_VERSION, $this->minWoocommerceVersion);
        $woocommercePluginPageUrl = $this->buildInstallPluginPageLink('woocommerce');

        if (! $wcVersionIsOk) {
            $this->errors[] = sprintf(
            /* translators: %1$s is replaced with WooCommerce plugin installation page url. */
                __(
                    '<strong>WooCommerce eCurring gateway plugin is inactive.</strong> Please, update <a href="%1$s">WooCommerce</a> plugin first.',
                    'woo-ecurring'
                ),
                $woocommercePluginPageUrl
            );
        }

        return $wcVersionIsOk;
    }

    /**
     * @return bool Whether Mollie plugin is active.
     */
    protected function checkMollieIsActive(): bool
    {
        $molliePluginBasename = $this->getMolliePluginBasename();
        $isMollieActive = $molliePluginBasename !== null && is_plugin_active($molliePluginBasename);

        if (! $isMollieActive) {
            $molliePluginPageUrl = $this->buildInstallPluginPageLink('mollie-payments-for-woocommerce');

            $this->errors[] = sprintf(
            /* translators: %1$s is replaced with Mollie plugin installation page url. */
                __(
                    '<strong>WooCommerce eCurring gateway plugin is inactive.</strong> Please, install and activate <a href="%1$s">Mollie Payments for WooCommerce</a> plugin first.',
                    'woo-ecurring'
                ),
                esc_url($molliePluginPageUrl)
            );
        }

        return $isMollieActive;
    }

    /**
     * Get base name (dir name/file name) of Mollie plugin.
     *
     * @return string|null
     */
    protected function getMolliePluginBasename(): ?string
    {
        if (! defined('M4W_FILE')) {
            return null;
        }

        if (! function_exists('plugin_basename')) {
            require_once ABSPATH . WPINC . '/plugin.php';
        }

        return $molliePluginBasename = plugin_basename(M4W_FILE);
    }

    /**
     * @return bool Whether current Mollie version is allowed
     */
    protected function checkMollieVersion(): bool
    {
        $isMollieVersionOk = false;

        if (defined('M4W_FILE')) {
            if (! function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $molliePluginData = get_plugin_data(M4W_FILE);
            $currentMollieVersion = $molliePluginData['Version'];

            $isMollieVersionOk = $this->checkVersion($currentMollieVersion, $this->minMollieVersion);
        }

        if (! $isMollieVersionOk) {
            $molliePluginPageUrl = $this->buildInstallPluginPageLink('mollie-payments-for-woocommerce');

            $this->errors[] = $mollieIsNotMinimalVersionMessage = sprintf(
            /* translators: %1$s is replaced with Mollie plugin installation page url. */
                __(
                    '<strong>WooCommerce eCurring gateway plugin is inactive.</strong> Please, update <a href="%1$s">Mollie Payments for WooCommerce</a> plugin first.',
                    'woo-ecurring'
                ),
                $molliePluginPageUrl
            );
        }

        return $isMollieVersionOk;
    }

    /**
     * Build url to given plugin installation page in the WP admin.
     *
     * @param string $pluginSlug The slug of the plugin to build link to.
     *
     * @return string Url of the install plugin page in WP admin.
     */
    protected function buildInstallPluginPageLink(string $pluginSlug): string
    {
        $pluginUrlQueryPart = http_build_query([
            'tab' => 'plugin-information',
            'plugin' => $pluginSlug,
        ]);

        return admin_url('plugin-install.php?' . $pluginUrlQueryPart);
    }

    /**
     * Check if $actualVersion less then $requiredVersion.
     *
     * @param string $actualVersion
     * @param string $requiredVersion
     *
     * @return bool
     */
    protected function checkVersion(string $actualVersion, string $requiredVersion): bool
    {
        try {
            $normalizedActualVersion = $this->normalizeVersion($actualVersion);
            $normalizedRequiredVersion = $this->normalizeVersion($requiredVersion);
        } catch (Exception $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Could not parse version string.' .
                    'Caught an exception when tried to normalize: %1$s',
                    $exception->getMessage()
                )
            );

            return false;
        }

        return version_compare($normalizedActualVersion, $normalizedRequiredVersion, '>=');
    }

    /**
     * Parse version string and return proper SevVer version string.
     *
     * @param string $version
     *
     * @return string
     *
     * @throws Exception If could not normalize version.
     */
    protected function normalizeVersion(string $version): string
    {
        return (string) $this->versionFactory->createVersionFromString($version);
    }
}
