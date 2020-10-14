<?php
/**
 * Plugin Name: WooCommerce eCurring gateway
 * Plugin URI: https://www.ecurring.com/
 * Description: Collect your subscription fees in WooCommerce with ease.
 * Version: 2.0.0
 * Author: eCurring
 * Requires at least: 4.6
 * Requires PHP: 7.2
 * Tested up to: 5.3
 * Text Domain: woo-ecurring
 * License: GPLv2 or later
 * WC requires at least: 4.0
 * WC tested up to: 4.5
 */

// Exit if accessed directly.
use eCurring\WooEcurring\Subscription\Actions;
use eCurring\WooEcurring\Subscription\Repository;

if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once 'includes/ecurring/wc/autoload.php';

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
 * Pro-actively check for required PHP JSON extension
 */
function ecurring_wc_check_json_extension() {
	if ( function_exists( 'extension_loaded' ) && ! extension_loaded( 'json' ) ) {
		remove_action( 'init', 'ecurring_wc_plugin_init' );
		add_action( 'admin_notices', 'ecurring_wc_plugin_inactive_json_extension' );
		return;
	}
}
add_action( 'plugins_loaded', 'ecurring_wc_check_json_extension' );

/**
 * Pro-actively check and communicate PHP version incompatibility for eCurring for WooCommerce
 */
function ecurring_wc_check_php_version() {
	if ( ! version_compare( PHP_VERSION, '5.6.0', ">=" ) ) {
		remove_action( 'init', 'ecurring_wc_plugin_init' );
		add_action( 'admin_notices', 'ecurring_wc_plugin_inactive_php' );
		return;
	}
}
add_action( 'plugins_loaded', 'ecurring_wc_check_php_version' );

/**
 * Check if WooCommerce is active and of a supported version
 */
function ecurring_wc_check_woocommerce_status() {
	if ( ! class_exists( 'WooCommerce' ) || version_compare( get_option( 'woocommerce_db_version' ), '3.0', '<' ) ) {
		remove_action('init', 'ecurring_wc_plugin_init');
		add_action( 'admin_notices', 'ecurring_wc_plugin_inactive' );
		return;
	}
}
add_action( 'plugins_loaded', 'ecurring_wc_check_woocommerce_status' );

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
        return;
    }
}

register_activation_hook(__FILE__, 'ecurring_wc_plugin_activation_hook');

function ecurring_wc_plugin_inactive_json_extension() {

	if ( ! is_admin() ) {
		return false;
	}

	echo '<div class="error"><p>';
	echo esc_html__( 'WooCommerce eCurring gateway requires the JSON extension for PHP. Enable it in your server or ask your webhoster to enable it for you.', 'woo-ecurring' );
	echo '</p></div>';

	return false;

}

function ecurring_wc_plugin_inactive_php() {

	if ( ! is_admin() ) {
		return false;
	}

	echo '<div class="error"><p>';
	echo __( 'eCurring for WooCommerce requires PHP 5.6 or higher. Your PHP version is outdated. Upgrade your PHP version (with help of your webhoster).', 'woo-ecurring' );
	echo '</p></div>';

	return false;

}

function ecurring_wc_plugin_inactive() {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

		echo '<div class="error"><p>';
		echo sprintf( esc_html__( '%1$seCurring for WooCommerce is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for it to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'woo-ecurring' ), '<strong>', '</strong>', '<a href="https://wordpress.org/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
		echo '</p></div>';
		return false;
	}

	if ( version_compare( get_option( 'woocommerce_db_version' ), '3.0', '<' ) ) {

		echo '<div class="error"><p>';
		echo sprintf( esc_html__( '%1$seCurring for WooCommerce is inactive.%2$s This version requires WooCommerce 3.0 or newer. Please %3$supdate WooCommerce to version 3.0 or newer &raquo;%4$s', 'woo-ecurring' ), '<strong>', '</strong>', '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
		echo '</p></div>';
		return false;

	}

	return '';
}

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

        $settingsHelper = new eCurring_WC_Helper_Settings();
        $apiHelper = new eCurring_WC_Helper_Api($settingsHelper);
        $actions = new Actions($apiHelper);
        $plugin = new \eCurring\WooEcurring\Plugin($actions);

        $plugin->init();

        add_action(
            'add_meta_boxes',
            function () {
                add_meta_box(
                    'ecurring_subscription_details',
                    'Details',
                    function ($post) {
                        (new \eCurring\WooEcurring\Subscription\Metabox\Display())->details($post);
                    },
                    'esubscriptions'
                );
                add_meta_box(
                    'ecurring_subscription_options',
                    'Options',
                    function ($post) {
                        (new \eCurring\WooEcurring\Subscription\Metabox\Display())->options($post);
                    },
                    'esubscriptions'
                );
            }
        );

        $actions = new Actions($apiHelper);
        add_action(
            'post_updated',
            function ($postId) use ($actions) {
                (new \eCurring\WooEcurring\Subscription\Metabox\Save($actions))->save($postId);

            }
        );

        add_action(
            'admin_enqueue_scripts',
            function () {
                if (get_current_screen()->id !== 'esubscriptions') {
                    return;
                }

                wp_enqueue_script(
                    'ecurring_admin_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/js/admin-subscriptions.js'),
                    ['jquery'],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/js/admin-subscriptions.js')
                        )
                    )
                );

                wp_enqueue_style(
                    'ecurring_admin_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/css/admin-subscriptions.css'),
                    [],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/css/admin-subscriptions.css')
                        )
                    )
                );
            }
        );

        add_action(
            'wp_enqueue_scripts',
            function () {
                // TODO load only on my account screen
                wp_enqueue_script(
                    'ecurring_customer_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/js/customer-subscriptions.js'),
                    ['jquery'],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/js/customer-subscriptions.js')
                        )
                    )
                );

                wp_localize_script(
                    'ecurring_customer_subscriptions',
                    'ecurring_customer_subscriptions',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                    ]
                );

                wp_enqueue_style(
                    'ecurring_customer_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/css/customer-subscriptions.css'),
                    [],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl(
                                'assets/css/customer-subscriptions.css'
                            )
                        )
                    )
                );
            }
        );

        add_filter(
            'request',
            function ($request) use ($apiHelper) {
                $webhook = filter_input(INPUT_GET, 'ecurring-webhook', FILTER_SANITIZE_STRING);

                if ($webhook === 'transaction') {
                    $log = new WC_Logger();
                    $log->add(
                        'transaction',
                        "transaction just received..."
                    );

                    $response = json_decode(file_get_contents('php://input'));
                    $transaction_id = filter_var(
                        $response->transaction_id,
                        FILTER_SANITIZE_STRING
                    );
                    $log->add(
                        'transaction',
                        "transaction {$transaction_id} webhook received in transaction webhook"
                    );

                    $subscription_id = filter_var(
                        $response->subscription_id,
                        FILTER_SANITIZE_STRING
                    );
                    $log->add(
                        'transaction',
                        "subscription {$subscription_id} webhook received in transaction webhook"
                    );

                    $subscription = json_decode(
                        $apiHelper->apiCall(
                            'GET',
                            "https://api.ecurring.com/subscriptions/{$subscription_id}"
                        )
                    );
                    $postSubscription = new eCurring\WooEcurring\Subscription\Repository();
                    $postSubscription->update($subscription);

                    $log->add(
                        'transaction',
                        "transaction {$transaction_id} and subscription {$subscription_id} webhook were received"
                    );
                }


                if ($webhook === 'subscription') {
                    $response = json_decode(file_get_contents('php://input'));
                    $subscription_id = filter_var(
                        $response->subscription_id,
                        FILTER_SANITIZE_STRING
                    );

                    $subscription = json_decode(
                        $apiHelper->apiCall(
                            'GET',
                            "https://api.ecurring.com/subscriptions/{$subscription_id}"
                        )
                    );
                    $postSubscription = new eCurring\WooEcurring\Subscription\Repository();
                    $postSubscription->update($subscription);

                    $log = new WC_Logger();
                    $log->add(
                        'subscription-webhook',
                        "subscription-webhook {$subscription_id} was received"
                    );
                }

                return $request;
            }
        );

        add_action(
            'admin_menu',
            function () {
                add_menu_page(
                    'eCurring Settings',
                    'eCurring',
                    'administrator',
                    __FILE__,
                    function () { ?>
<div class="wrap">
<h1>eCurring Settings</h1>
<form method="post" action="options.php">
    <?php settings_fields( 'ecurring-settings-group' ); ?>
    <?php do_settings_sections( 'ecurring-settings-group' ); ?>
    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">Allow subscription options for customers</th>
            <td><fieldset><legend class="screen-reader-text"><span>Allow subscription options for customers</span></legend>
                    <label for="ecurring_customer_subscription_pause">
                        <input name="ecurring_customer_subscription_pause" type="checkbox" value="1"
                            <?php checked( get_option('ecurring_customer_subscription_pause'), '1');?>>
                        Pause Subscription
                    </label>
                    <br>
                    <label for="ecurring_customer_subscription_switch">
                        <input name="ecurring_customer_subscription_switch" type="checkbox" value="1"
                            <?php checked( get_option('ecurring_customer_subscription_switch'), '1');?>>
                        Switch Subscription
                    </label>
                    <br>
                    <label for="ecurring_customer_subscription_cancel">
                        <input name="ecurring_customer_subscription_cancel" type="checkbox" value="1"
                            <?php checked( get_option('ecurring_customer_subscription_cancel'), '1');?>>
                        Cancel Subscription
                    </label>
                </fieldset></td>
        </tr>
        </tbody>
    </table>

    <?php submit_button(); ?>
</form>
</div>
                        <?php
                    }
                );
            }
        );

        add_action(
            'admin_init',
            function () {
                register_setting('ecurring-settings-group', 'ecurring_customer_subscription_pause');
                register_setting(
                    'ecurring-settings-group',
                    'ecurring_customer_subscription_switch'
                );
                register_setting(
                    'ecurring-settings-group',
                    'ecurring_customer_subscription_cancel'
                );
            }
        );

        add_action(
            'woocommerce_account_ecurring-subscriptions_endpoint',
            function () use ($apiHelper) {
                (new \eCurring\WooEcurring\Customer\Subscriptions($apiHelper))->display();
            }
        );

        add_filter(
            'query_vars',
            function ($vars) {
                $vars[] = 'ecurring-subscriptions';
                return $vars;
            },
            0
        );

        add_filter(
            'the_title',
            function ($title) {
                global $wp_query;
                if (isset($wp_query->query_vars['ecurring-subscriptions']) && in_the_loop()) {
                    return __('Subscriptions', 'woo-ecurring');
                }

                return $title;
            },
            10,
            2
        );

        add_action(
            'wp_ajax_ecurring_customer_subscriptions',
            function () use ($actions) {
                doSubscriptionAction($actions);
            }
        );
        add_action(
            'wp_ajax_nopriv_ecurring_customer_subscriptions',
            function () use ($actions) {
                doSubscriptionAction($actions);
            }
        );

    } catch (Throwable $throwable) {
        handleException($throwable);
    }
}

/**
 * @param Actions $actions
 * @throws Exception
 */
function doSubscriptionAction(Actions $actions): void
{
    $subscriptionType = filter_input(
        INPUT_POST,
        'ecurring_subscription_type',
        FILTER_SANITIZE_STRING
    );

    $subscriptionId = filter_input(
        INPUT_POST,
        'ecurring_subscription_id',
        FILTER_SANITIZE_STRING
    );

    $pauseSubscription = filter_input(
        INPUT_POST,
        'ecurring_pause_subscription',
        FILTER_SANITIZE_STRING
    );
    $resumeDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
    if ($pauseSubscription === 'specific-date') {
        $resumeDate = filter_input(
            INPUT_POST,
            'ecurring_resume_date',
            FILTER_SANITIZE_STRING
        );

        $resumeDate = (new DateTime($resumeDate))->format('Y-m-d\TH:i:sP');
    }

    $switchSubscription = filter_input(
        INPUT_POST,
        'ecurring_switch_subscription',
        FILTER_SANITIZE_STRING
    );
    $switchDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
    if ($switchSubscription === 'specific-date') {
        $switchDate = filter_input(
            INPUT_POST,
            'ecurring_switch_date',
            FILTER_SANITIZE_STRING
        );

        $switchDate = (new DateTime($switchDate))->format('Y-m-d\TH:i:sP');
    }

    $cancelSubscription = filter_input(
        INPUT_POST,
        'ecurring_cancel_subscription',
        FILTER_SANITIZE_STRING
    );
    $cancelDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
    if ($cancelSubscription === 'specific-date') {
        $cancelDate = filter_input(
            INPUT_POST,
            'ecurring_cancel_date',
            FILTER_SANITIZE_STRING
        );

        $cancelDate = (new DateTime($cancelDate))->format('Y-m-d\TH:i:sP');
    }

    switch ($subscriptionType) {
        case 'pause':
            $response = json_decode(
                $actions->pause(
                    $subscriptionId,
                    $resumeDate
                )
            );
            updatePostSubscription($response);
            break;
        case 'resume':
            $response = json_decode($actions->resume($subscriptionId));
            updatePostSubscription($response);
            break;
        case 'switch':
            $cancel = json_decode($actions->cancel($subscriptionId, $switchDate));
            updatePostSubscription($cancel);

            $productId = filter_input(
                INPUT_POST,
                'ecurring_subscription_plan',
                FILTER_SANITIZE_STRING
            );

            $subscriptionWebhookUrl = add_query_arg(
                'ecurring-webhook',
                'subscription',
                home_url('/')
            );
            $transactionWebhookUrl = add_query_arg(
                'ecurring-webhook',
                'transaction',
                home_url('/')
            );

            $response = json_decode(
                $actions->create(
                    [
                        'data' => [
                            'type' => 'subscription',
                            'attributes' => [
                                'customer_id' => $cancel->data->relationships->customer->data->id,
                                'subscription_plan_id' => $productId,
                                'mandate_code' => $cancel->data->attributes->mandate_code,
                                'mandate_accepted' => true,
                                'mandate_accepted_date' => $cancel->data->attributes->mandate_accepted_date,
                                'confirmation_sent' => 'true',
                                'subscription_webhook_url' => $subscriptionWebhookUrl,
                                'transaction_webhook_url' => $transactionWebhookUrl,
                                'status' => 'active',
                                "start_date" => $switchDate,
                            ],
                        ],
                    ]
                )
            );

            $postSubscription = new Repository();
            $postSubscription->create($response->data);
            break;
        case 'cancel':
            $response = json_decode(
                $actions->cancel($subscriptionId, $cancelDate)
            );
            updatePostSubscription($response);
            break;
    }

    //wp_send_json(['response' => $response]);
    wp_die();
}

/**
 * @param $response
 */
function updatePostSubscription($response)
{
    $subscriptionPosts = get_posts(
        [
            'post_type' => 'esubscriptions',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]
    );

    foreach ($subscriptionPosts as $post) {
        $postSubscriptionId = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);

        if ($postSubscriptionId && $postSubscriptionId === $response->data->id) {
            update_post_meta($post->ID, '_ecurring_post_subscription_id', $response->data->id);
            update_post_meta(
                $post->ID,
                '_ecurring_post_subscription_links',
                $response->data->links
            );
            update_post_meta(
                $post->ID,
                '_ecurring_post_subscription_attributes',
                $response->data->attributes
            );
            update_post_meta(
                $post->ID,
                '_ecurring_post_subscription_relationships',
                $response->data->relationships
            );
        }
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
