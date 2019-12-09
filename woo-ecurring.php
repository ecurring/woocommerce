<?php
/**
 * Plugin Name: WooCommerce eCurring gateway
 * Plugin URI: https://www.ecurring.com/
 * Description: Collect your subscription fees in WooCommerce with ease.
 * Version: 1.1.3
 * Author: eCurring
 * Requires at least: 4.6
 * Tested up to: 5.3
 * Text Domain: woo-ecurring
 * License: GPLv2 or later
 * WC requires at least: 3.0.0
 * WC tested up to: 3.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once 'includes/ecurring/wc/autoload.php';

/**
 * Plugin constants
 */

if ( ! defined( 'WOOECUR_PLUGIN_ID' ) ) {
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
		if ('mark_processing' === $key)
			$new_actions['mark_ecurring-retry'] = __( 'Change status to Retrying payment at eCurring', 'Order status', 'woo-ecurring' );

		$new_actions[$key] = $action;
	}
	return $new_actions;
}

add_action('init', 'ecurring_wc_plugin_init');
