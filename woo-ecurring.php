<?php
/**
 * Plugin Name: Mollie Subscriptions
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

// Exit if accessed directly.
use Ecurring\WooEcurring\EnvironmentChecker\EnvironmentChecker;
use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\SubscriptionsJob;
use Ecurring\WooEcurring\Subscription\PostType;
use Ecurring\WooEcurring\Subscription\Metabox\Display;
use Ecurring\WooEcurring\Subscription\Metabox\Save;
use Ecurring\WooEcurring\Subscription\Metabox\Metabox;
use Ecurring\WooEcurring\Assets;
use Ecurring\WooEcurring\WebHook;
use Ecurring\WooEcurring\Settings;
use Ecurring\WooEcurring\Customer\MyAccount;
use Ecurring\WooEcurring\Customer\Subscriptions;

if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once 'includes/ecurring/wc/autoload.php';
require_once 'vendor/autoload.php';

/**
 * Plugin constants
 */

if (!defined('WOOECUR_PLUGIN_ID')) {
	define( 'WOOECUR_PLUGIN_ID', 'woo-ecurring' );
}

if ( ! defined( 'WOOECUR_PLUGIN_TITLE' ) ) {
	define( 'WOOECUR_PLUGIN_TITLE', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WOOECUR_PLUGIN_VERSION' ) ) {
	define( 'WOOECUR_PLUGIN_VERSION', '1.1.0' );
}

if ( ! defined( 'WOOECUR_DB_VERSION' ) ) {
	define( 'WOOECUR_DB_VERSION', '1.1.0' );
}

if ( ! defined( 'WOOECUR_DB_VERSION_PARAM_NAME' ) ) {
	define( 'WOOECUR_DB_VERSION_PARAM_NAME', 'ecurring-db-version' );
}

if ( ! defined( 'WOOECUR_PENDING_PAYMENT_DB_TABLE_NAME' ) ) {
	define( 'WOOECUR_PENDING_PAYMENT_DB_TABLE_NAME', 'ecurring_pending_payment' );
}

// Plugin folder URL.
if ( ! defined( 'WOOECUR_PLUGIN_URL' ) ) {
	define( 'WOOECUR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin directory
if ( ! defined( 'WOOECUR_PLUGIN_DIR' ) ) {
	define( 'WOOECUR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * Called when plugin is loaded
 */
function ecurring_wc_plugin_init() {

	// Register autoloader
	eCurring_WC_Autoload::register();

	// Setup and start plugin
	eCurring_WC_Plugin::init();

	// Add endpoint for eCurring Subscriptions
	add_rewrite_endpoint( 'ecurring-subscriptions', EP_ROOT | EP_PAGES );

}

/**
 * Called when plugin is activated
 */
function ecurring_wc_plugin_activation_hook ()
{

	if ( ! class_exists( 'WooCommerce' ) || version_compare( get_option( 'woocommerce_db_version' ), '3.0', '<' ) ) {
		remove_action('init', 'ecurring_wc_plugin_init');
		add_action( 'admin_notices', 'ecurring_wc_plugin_inactive' );
		return;
	}

    // Register eCurring autoloader
   eCurring_WC_Autoload::register();

    $status_helper = eCurring_WC_Plugin::getStatusHelper();

    if (!$status_helper->isCompatible())
    {
        $title   = 'Could not activate plugin ' . WOOECUR_PLUGIN_TITLE;
        $message = '<h1><strong>Could not activate plugin ' . WOOECUR_PLUGIN_TITLE . '</strong></h1><br/>'
                 . implode('<br/>', $status_helper->getErrors());

        wp_die($message, $title, array('back_link' => true));
    }
}

register_activation_hook(__FILE__, 'ecurring_wc_plugin_activation_hook');

// Add custom order status "Retrying payment at eCurring"
add_action( 'init', 'eCurringRegisterNewStatusAsPostStatus', 10, 2);
add_filter( 'wc_order_statuses', 'eCurringRegisterNewStatusAsOrderStatus', 10, 2);
add_filter( 'bulk_actions-edit-shop_order', 'eCurringRegisterNewStatusAsBulkAction', 50, 1 );

/**
 *  Add 'Retrying payment at eCurring' status
 */
function eCurringRegisterNewStatusAsPostStatus() {
	register_post_status( 'wc-ecurring-retry', array(
		'label'                     => __( 'Retrying payment at eCurring', 'Order status', 'woo-ecurring' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Retrying payment at eCurring <span class="count">(%s)</span>', 'Retrying payment at eCurring<span class="count">(%s)</span>', 'woo-ecurring' )
	) );
}

/**
 * @param $order_statuses
 *
 * @return array
 */
function eCurringRegisterNewStatusAsOrderStatus( $order_statuses ) {

	$new_order_statuses = array();

	foreach ( $order_statuses as $key => $status ) {
		$new_order_statuses[ $key ] = $status;
		if ( 'wc-processing' === $key ) {
			$new_order_statuses['wc-ecurring-retry'] = __( 'Retrying payment at eCurring', 'Order status', 'woo-ecurring' );
		}
	}
	return $new_order_statuses;

}

/**
 * @param $actions
 *
 * @return array
 */
function eCurringRegisterNewStatusAsBulkAction( $actions ) {
	$new_actions = array();

	foreach ($actions as $key => $action) {
        if ('mark_processing' === $key) {
            $new_actions['mark_ecurring-retry'] = __('Change status to Retrying payment at eCurring',
                'Order status', 'woo-ecurring');
        }

        $new_actions[$key] = $action;
    }
    return $new_actions;
}

add_action('init', 'ecurring_wc_plugin_init');

/**
 * @throws Throwable
 */
function initialize()
{
    try {
        if (is_readable(__DIR__ . '/vendor/autoload.php')) {
            include_once __DIR__ . '/vendor/autoload.php';
        }

        require_once 'includes/ecurring/wc/helper/settings.php';
        require_once 'includes/ecurring/wc/helper/api.php';
        require_once 'includes/ecurring/wc/plugin.php';

        $environmentChecker = new EnvironmentChecker('7.2', '3.9');
        if (!$environmentChecker->checkEnvironment()) {
            foreach ($environmentChecker->getErrors() as $errorMessage) {
                errorNotice($errorMessage);
            }
        }

        $settingsHelper = new eCurring_WC_Helper_Settings();
        $apiHelper = new eCurring_WC_Helper_Api($settingsHelper);
        $actions = new Actions($apiHelper);
        $repository = new Repository();
        $display = new Display();
        $save = new Save($actions);
        $subscriptions = new Subscriptions($apiHelper);

        (new SubscriptionsJob($actions, $repository))->init();
        (new Metabox($display, $save))->init();
        (new PostType())->init();
        (new Assets())->init();
        (new WebHook($apiHelper, $repository))->init();
        (new Settings())->init();
        (new MyAccount($apiHelper, $actions, $repository, $subscriptions))->init();

        add_action(
            'woocommerce_payment_complete',
            function (int $orderId) use ($repository, $apiHelper) {
                $order = wc_get_order($orderId);
                $subscriptionId = $order->get_meta('_ecurring_subscription_id', true);

                if ($subscriptionId) {
                    $response = json_decode(
                        $apiHelper->apiCall(
                            'GET',
                            "https://api.ecurring.com/subscriptions/{$subscriptionId}"
                        )
                    );

                    $repository->create($response->data);
                }
            }
        );

    } catch (Throwable $throwable) {
        handleException($throwable);
    }
}

add_action('plugins_loaded', __NAMESPACE__ . '\\initialize', PHP_INT_MAX);

/**
 * Handle any exception that might occur during plugin setup.
 *
 * @param Throwable $throwable The Exception
 *
 * @return void
 */
function handleException(Throwable $throwable)
{
    do_action('ecurring.woo-ecurring.critical', $throwable);

    errorNotice(
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
function errorNotice(string $message)
{
    foreach (['admin_notices', 'network_admin_notices'] as $hook) {
        add_action(
            $hook,
            function () use ($message) {
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
