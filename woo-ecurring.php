<?php

/**
 * Plugin Name: WooCommerce eCurring gateway
 * Plugin URI: https://www.ecurring.com/
 * Description: Collect your subscription fees in WooCommerce with ease.
 * Version: 1.2.0
 * Author: Mollie
 * Requires at least: 4.6
 * Requires PHP: 7.2
 * Tested up to: 5.5
 * Text Domain: woo-ecurring
 * License: GPLv2 or later
 * WC requires at least: 4.0
 * WC tested up to: 4.6
 */

use Dhii\Versions\StringVersionFactory;
use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Api\SubscriptionPlans;
use Ecurring\WooEcurring\EnvironmentChecker\EnvironmentChecker;
use Ecurring\WooEcurring\Settings\SettingsCrud;
use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateFactory;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusFactory;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcher;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactory;
use Ecurring\WooEcurring\SubscriptionsJob;
use Ecurring\WooEcurring\Subscription\PostType;
use Ecurring\WooEcurring\Subscription\Metabox\Display;
use Ecurring\WooEcurring\Subscription\Metabox\Save;
use Ecurring\WooEcurring\Subscription\Metabox\Metabox;
use Ecurring\WooEcurring\Assets;
use Ecurring\WooEcurring\WebHook;
use Ecurring\WooEcurring\Customer\MyAccount;
use Ecurring\WooEcurring\Customer\Subscriptions;
use Ecurring\WooEcurring\Api\Subscriptions as SubscriptionsApi;
use Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher\SubscriptionPlanSwitcher;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin constants
 */

if (!defined('WOOECUR_PLUGIN_ID')) {
    define('WOOECUR_PLUGIN_ID', 'woo-ecurring');
}

if (! defined('WOOECUR_PLUGIN_TITLE')) {
    define('WOOECUR_PLUGIN_TITLE', plugin_dir_path(__FILE__));
}

if (! defined('WOOECUR_PLUGIN_VERSION')) {
    define('WOOECUR_PLUGIN_VERSION', '1.1.0');
}

if (! defined('WOOECUR_DB_VERSION')) {
    define('WOOECUR_DB_VERSION', '1.1.0');
}

if (! defined('WOOECUR_DB_VERSION_PARAM_NAME')) {
    define('WOOECUR_DB_VERSION_PARAM_NAME', 'ecurring-db-version');
}

if (! defined('WOOECUR_PENDING_PAYMENT_DB_TABLE_NAME')) {
    define('WOOECUR_PENDING_PAYMENT_DB_TABLE_NAME', 'ecurring_pending_payment');
}

// Plugin folder URL.
if (! defined('WOOECUR_PLUGIN_URL')) {
    define('WOOECUR_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Plugin directory
if (! defined('WOOECUR_PLUGIN_DIR')) {
    define('WOOECUR_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

//Plugin main file
if (! defined('WOOECUR_PLUGIN_FILE')) {
    define('WOOECUR_PLUGIN_FILE', __FILE__);
}

/**
 *  Add 'Retrying payment at eCurring' status
 */
function eCurringRegisterNewStatusAsPostStatus()
{

    register_post_status('wc-ecurring-retry', [
        'label' => _x('Retrying payment at eCurring', 'Order status', 'woo-ecurring'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop(
            'Retrying payment at eCurring <span class="count">(%s)</span>',
            'Retrying payment at eCurring<span class="count">(%s)</span>',
            'woo-ecurring'
        ),
    ]);
}

/**
 * @param $order_statuses
 *
 * @return array
 */
function eCurringRegisterNewStatusAsOrderStatus($order_statuses)
{

    $new_order_statuses = [];

    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[ $key ] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-ecurring-retry'] = _x(
                'Retrying payment at eCurring',
                'Order status',
                'woo-ecurring'
            );
        }
    }
    return $new_order_statuses;
}

/**
 * @param $actions
 *
 * @return array
 */
function eCurringRegisterNewStatusAsBulkAction($actions)
{

    $new_actions = [];

    foreach ($actions as $key => $action) {
        if ('mark_processing' === $key) {
            $new_actions['mark_ecurring-retry'] = _x(
                'Change status to Retrying payment at eCurring',
                'Order status',
                'woo-ecurring'
            );
        }

        $new_actions[$key] = $action;
    }
    return $new_actions;
}

/**
 * @throws Throwable
 */
function eCurringInitialize()
{
    try {
        if (is_readable(__DIR__ . '/vendor/autoload.php')) {
            include_once __DIR__ . '/vendor/autoload.php';
        }

        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        require_once 'includes/ecurring/wc/autoload.php';
        require_once 'includes/ecurring/wc/helper/settings.php';
        require_once 'includes/ecurring/wc/helper/api.php';
        require_once 'includes/ecurring/wc/plugin.php';

        add_action('init', static function () {
            // Register autoloader
            eCurring_WC_Autoload::register();

            // Setup and start plugin
            eCurring_WC_Plugin::init();

            // Add endpoint for eCurring Subscriptions
            add_rewrite_endpoint('ecurring-subscriptions', EP_ROOT | EP_PAGES);
        });

        $versionFactory = new StringVersionFactory();

        $environmentChecker = new EnvironmentChecker(
            '7.2',
            '3.9',
            '6.0.0',
            $versionFactory
        );
        if (!$environmentChecker->checkEnvironment()) {
            foreach ($environmentChecker->getErrors() as $errorMessage) {
                eCurringErrorNotice($errorMessage);
            }

            return;
        }

        $settingsHelper = new eCurring_WC_Helper_Settings();
        $apiHelper = new eCurring_WC_Helper_Api($settingsHelper);
        $customerApi = new Customers($apiHelper);
        $apiClient = new ApiClient($settingsHelper->getApiKey() ?? '');
        $subscriptionMandateFactory = new SubscriptionMandateFactory();
        $subscriptionStatusFactory = new SubscriptionStatusFactory();
        $subscriptionsFactory = new DataBasedSubscriptionFactory(
            $subscriptionMandateFactory,
            $subscriptionStatusFactory
        );
        $repository = new Repository($subscriptionsFactory, $customerApi);
        $subscriptionsApi = new SubscriptionsApi($apiHelper, $apiClient, $subscriptionsFactory);

        $subscriptionStatusSwitcher = new SubscriptionStatusSwitcher($subscriptionsApi, $repository);
        $subscriptionPlanSwitcher = new SubscriptionPlanSwitcher(
            $subscriptionStatusSwitcher,
            $subscriptionsApi,
            $repository
        );
        $display = new Display();
        $save = new Save($subscriptionStatusSwitcher, $subscriptionPlanSwitcher);
        $subscriptionPlans = new SubscriptionPlans($apiHelper);
        $settingsCrud = new SettingsCrud();
        $subscriptions = new Subscriptions($customerApi, $subscriptionPlans, $settingsCrud);

        (new SubscriptionsJob($repository, $subscriptionsFactory, $subscriptionsApi))->init();
        (new Metabox($display, $save))->init();
        (new PostType($apiHelper))->init();
        (new Assets())->init();
        (new WebHook($subscriptionsApi, $repository))->init();
        (new MyAccount($subscriptions, $subscriptionPlanSwitcher, $subscriptionStatusSwitcher))->init();

        // Add custom order status "Retrying payment at eCurring"
        add_action('init', 'eCurringRegisterNewStatusAsPostStatus', 10, 2);
        add_filter('wc_order_statuses', 'eCurringRegisterNewStatusAsOrderStatus', 10, 2);
        add_filter('bulk_actions-edit-shop_order', 'eCurringRegisterNewStatusAsBulkAction', 50, 1);
    } catch (Throwable $throwable) {
        eCurringHandleException($throwable);
    }
}

add_action('plugins_loaded', 'eCurringInitialize', PHP_INT_MAX);

/**
 * Handle any exception that might occur during plugin setup.
 *
 * @param Throwable $throwable The Exception
 *
 * @return void
 */
function eCurringHandleException(Throwable $throwable)
{
    do_action('ecurring.woo-ecurring.critical', $throwable);

    eCurringErrorNotice(
        sprintf(
            '<strong>Error:</strong> %s <br><pre>%s</pre>',
            $throwable->getMessage(),
            $throwable->getTraceAsString()
        )
    );
}

/**
 * Display an error message in the WP admin.
 *
 * @param string $message The message content
 *
 * @return void
 */
function eCurringErrorNotice(string $message)
{
    foreach (['admin_notices', 'network_admin_notices'] as $hook) {
        add_action(
            $hook,
            static function () use ($message) {
                $class = 'notice notice-error';
                printf(
                    '<div class="%1$s"><p>%2$s</p></div>',
                    esc_attr($class),
                    wp_kses_post($message)
                );
            }
        );
    }
}
