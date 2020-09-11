<?php

// Require Webhook functions
require_once dirname(dirname(dirname(__FILE__))) . '/webhook_functions.php';

class eCurring_WC_Plugin
{

    /**
     * @var bool
     */
    private static $initiated = false;

    /**
     * @var array
     */
    public static $GATEWAYS = array(
        'eCurring_WC_Gateway_eCurring',
    );

    private function __construct () {}

    /**
     * Initialize plugin
     */
	public static function init() {
		if ( self::$initiated ) {
			/*
			 * Already initialized
			 */
			return;
		}

		$plugin_basename = self::getPluginFile();
		$data_helper     = self::getDataHelper();

		// When page 'WooCommerce -> Checkout -> Checkout Options' is saved
		add_action( 'woocommerce_settings_save_checkout', array ( $data_helper, 'deleteTransients' ) );

		// Add eCurring gateways
		add_filter( 'woocommerce_payment_gateways', array ( __CLASS__, 'addGateways' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . $plugin_basename, array ( __CLASS__, 'addPluginActionLinks' ) );

		// Listen to return URL call
		add_action( 'woocommerce_api_ecurring_return', array ( __CLASS__, 'eCurringReturn' ) );

		// Show eCurring instructions on order details page
		add_action( 'woocommerce_order_details_after_order_table', array ( __CLASS__, 'onOrderDetails' ), 10, 1 );

		// Set order to paid and processed when eventually completed without eCurring
		add_action( 'woocommerce_payment_complete', array ( __CLASS__, 'setOrderPaidByOtherGateway' ), 10, 1 );

		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array ( __CLASS__, 'eCurringEnqueueScriptsAndStyles' ) );

		// admin scripts and styles
		add_action( 'admin_enqueue_scripts', array ( __CLASS__, 'eCurringEnqueueScriptsAndStylesAdmin' ) );

		// Adding eCurring tab to the WC Product
		add_filter('woocommerce_product_data_tabs', array ( __CLASS__, 'eCurringProductDataTab'), 99, 1);

		// Adding list of products to the eCurring tab
		add_action('woocommerce_product_data_panels', array ( __CLASS__, 'eCurringProductDataFields'));

		// Save eCurring product in the product post meta - "_ecurring_subscription_plan"
		add_action('woocommerce_process_product_meta', array ( __CLASS__, 'eCurringProcessProductMetaFieldsSave'));

		// Empty cart if adding eCurring subscription product.
		// Or if adding non-eCurring product to cart, where an eCurring product was already added
		add_filter('woocommerce_add_cart_item_data', array ( __CLASS__, 'eCurringCartUpdate'), 10, 3);

		// Hide coupon in cart and checkout if there is eCurring product
		add_filter('woocommerce_coupons_enabled', array ( __CLASS__, 'eCurringHideCouponField'));

		// eCurring WC Coupon notice show
		add_action('admin_notices', array ( __CLASS__, 'eCurringCouponNotice'));

		// eCurring WC Product edit page notice
		add_action('admin_notices', array ( __CLASS__, 'eCurringAdminProductPageNotice'), 11);

		// eCurring WC Coupon notice dismiss
		add_action('wp_ajax_dismiss_coupon_disabled', array ( __CLASS__, 'eCurringDismissCouponDisabled'));

		// Left only eCurring payment gateway if there is eCurring product, and hide payment gateway div.
		// Otherwise just exclude eCurring payment gateway.
		add_filter('woocommerce_available_payment_gateways', array ( __CLASS__, 'eCurringFilterGateways'), 1);

		// eCurring add to cart Ajax redirect
		add_action('wp_ajax_ecurring_add_to_cart_redirect', array ( __CLASS__, 'eCurringAddToCartRedirectAjax'));
		add_action('wp_ajax_nopriv_ecurring_add_to_cart_redirect', array ( __CLASS__, 'eCurringAddToCartRedirectAjax'));

		// Redirect URL after successful adding to cart
		add_filter('woocommerce_add_to_cart_redirect', array ( __CLASS__, 'eCurringRedirectToCheckout'));

		// Mandate accept description
		add_action('woocommerce_checkout_terms_and_conditions', array ( __CLASS__, 'eCurringCheckoutPrivacyPolicyText'), 20);

		// Disable WC sale price for eCurring products
		add_filter( 'woocommerce_product_get_sale_price', array ( __CLASS__, 'eCurringDisableSalePrice'), 50, 2 );
		add_filter( 'woocommerce_product_get_price', array ( __CLASS__, 'eCurringDisableSalePrice'), 50, 2 );

		// eCurring add to cart button text
		add_filter('woocommerce_product_add_to_cart_text', array ( __CLASS__, 'eCurringAddToCartText'), 10, 2);
		add_filter('woocommerce_product_single_add_to_cart_text', array ( __CLASS__, 'eCurringAddToCartText'), 10, 2);

		// eCurring add to cart button text
		add_filter('woocommerce_is_sold_individually', array ( __CLASS__, 'eCurringDisableQuantity'), 10, 2);

		// Disable "Pay" button/action on My Account for orders with eCurring
		add_filter( 'woocommerce_my_account_my_orders_actions', array ( __CLASS__, 'eCurringRemovePayFromMyAccountOrders' ), 10, 2 );

		// Disable eCurring on "Pay for order" page
		add_filter( 'woocommerce_available_payment_gateways', array ( __CLASS__, 'disableeCurringOnPayForOrder' ), 10, 1 );

		// Add eCurring Subscriptions to WooCommerce My Account
		add_action( 'init', array ( __CLASS__, 'eCurringSubscriptionsEndpoint' ), 10, 2 );

		add_filter( 'woocommerce_account_menu_items', array ( __CLASS__, 'eCurringSubscriptionsMyAccount'), 10, 1 );

		add_filter( 'query_vars', array ( __CLASS__, 'eCurringSubscriptionsQueryVars' ), 0 );

		add_action( 'woocommerce_account_ecurring-subscriptions_endpoint', array ( __CLASS__,'eCurringSubscriptionsContent' ));

		add_filter( 'the_title', array( __CLASS__, 'eCurringSubscriptionsUpdateTitle'), 10, 2 );

		// Cancel My Account > Subscriptions
		add_action('wp_ajax_ecurring_my_account_cancel_subscription', array ( __CLASS__, 'eCurringSubscriptionsCancelSubscription'));
		add_action('wp_ajax_nopriv_ecurring_my_account_cancel_subscription', array ( __CLASS__, 'eCurringSubscriptionsCancelSubscription'));

		// Add 'eCurring details' metabox to WooCommerce Order Edit
		add_action( 'add_meta_boxes', array ( __CLASS__, 'eCurringAddOrdersMetaBox') );

		// Mark plugin initiated
		self::$initiated = true;
	}

    /**
     * Payment return url callback
     */
    public static function eCurringReturn ()
    {

        $data_helper = self::getDataHelper();

	    $order_id = ! empty( $_GET['order_id'] ) ? sanitize_text_field( $_GET['order_id'] ) : null;
	    $key      = ! empty( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : null;

        $order    = $data_helper->getWcOrder($order_id);

        if (!$order)
        {
            self::setHttpResponseCode(404);
            self::debug(__METHOD__ . ":  Could not find order $order_id.");
            return;
        }

        if (!$order->key_is_valid($key))
        {
            self::setHttpResponseCode(401);
            self::debug(__METHOD__ . ":  Invalid key $key for order $order_id.");
            return;
        }

        $gateway = $data_helper->getWcPaymentGatewayByOrder($order);

        if (!$gateway)
        {
            self::setHttpResponseCode(404);

            self::debug(__METHOD__ . ":  Could not find gateway for order $order_id.");
            return;
        }

        if (!($gateway instanceof eCurring_WC_Gateway_Abstract))
        {
            self::setHttpResponseCode(400);
            self::debug(__METHOD__ . ": Invalid gateway " . get_class($gateway) . " for this plugin. Order $order_id.");
            return;
        }

        /** @var eCurring_WC_Gateway_Abstract $gateway */

        $redirect_url = $gateway->getReturnRedirectUrlForOrder($order);

        // Add utm_nooverride query string
        $redirect_url = add_query_arg(array(
            'utm_nooverride' => 1,
        ), $redirect_url);

        self::debug(__METHOD__ . ": Redirect url on return order " . $gateway->id . ", order $order_id: $redirect_url");

        wp_safe_redirect($redirect_url);
    }

    /**
     * @param WC_Order $order
     */
    public static function onOrderDetails (WC_Order $order)
    {
        if (eCurring_WC_Plugin::getDataHelper()->is_order_received_page())
        {
            /**
             * Do not show instruction again below details on order received page
             * Instructions already displayed on top of order received page by $gateway->thankyou_page()
             *
             * @see eCurring_WC_Gateway_Abstract::thankyou_page
             */
            return;
        }

        $gateway = eCurring_WC_Plugin::getDataHelper()->getWcPaymentGatewayByOrder($order);

        if (!$gateway || !($gateway instanceof eCurring_WC_Gateway_Abstract))
        {
            return;
        }

        /** @var eCurring_WC_Gateway_Abstract $gateway */

        $gateway->displayInstructions($order);
    }

    /**
     * Set HTTP status code
     *
     * @param int $status_code
     */
    public static function setHttpResponseCode ($status_code)
    {
        if (PHP_SAPI !== 'cli' && !headers_sent())
        {
            if (function_exists("http_response_code"))
            {
                http_response_code($status_code);
            }
            else
            {
                header(" ", TRUE, $status_code);
            }
        }
    }

    /**
     * Add eCurring gateways
     *
     * @param array $gateways
     * @return array
     */
	public static function addGateways( array $gateways ) {

		$gateways = array_merge( $gateways, self::$GATEWAYS );

		// Return if function get_current_screen() is not defined
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $gateways;
		}

		// Try getting get_current_screen()
		$current_screen = get_current_screen();

		// Return if get_current_screen() isn't set
		if ( ! $current_screen ) {
			return $gateways;
		}

		// Remove old MisterCash (only) from WooCommerce Payment settings
		if ( is_admin() && ! empty( $current_screen->base ) && $current_screen->base == 'woocommerce_page_wc-settings' ) {
			if ( ( $key = array_search( 'eCurring_WC_Gateway_MisterCash', $gateways ) ) !== false ) {
				unset( $gateways[ $key ] );
			}
		}

		return $gateways;
	}

	/**
	 * Add a WooCommerce notification message
	 *
	 * @param string $message Notification message
	 * @param string $type    One of notice, error or success (default notice)
	 *
	 * @return $this
	 */
	public static function addNotice( $message, $type = 'notice' ) {
		$type = in_array( $type, array ( 'notice', 'error', 'success' ) ) ? $type : 'notice';

		// Check for existence of new notification api (WooCommerce >= 2.1)
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, $type );
		} else {
			$woocommerce = WooCommerce::instance();

			switch ( $type ) {
				case 'error' :
					$woocommerce->add_error( $message );
					break;
				default :
					$woocommerce->add_message( $message );
					break;
			}
		}
	}

    /**
     * Log messages to WooCommerce log
     *
     * @param mixed $message
     * @param bool  $set_debug_header Set X-eCurring-Debug header (default false)
     */
    public static function debug ($message, $set_debug_header = false)
    {
        // Convert message to string
        if (!is_string($message))
        {
            $message = ( version_compare( WC_VERSION, '3.0', '<' ) ) ? print_r($message, true) : wc_print_r($message, true);
        }

        // Set debug header
        if ($set_debug_header && PHP_SAPI !== 'cli' && !headers_sent())
        {
            header("X-eCurring-Debug: $message");
        }

	    // Log message
	    if ( self::getSettingsHelper()->isDebugEnabled() ) {

		    if ( version_compare( WC_VERSION, '3.0', '<' ) ) {

			    static $logger;

			    if ( empty( $logger ) ) {
				    $logger = new WC_Logger();
			    }

			    $logger->add( WOOECUR_PLUGIN_ID . '-' . date( 'Y-m-d' ), $message );

		    } else {

			    $logger = wc_get_logger();

			    $context = array ( 'source' => WOOECUR_PLUGIN_ID . '-' . date( 'Y-m-d' ) );

			    $logger->debug( $message, $context );

		    }

	    }
    }

    /**
     * Get location of main plugin file
     *
     * @return string
     */
    public static function getPluginFile ()
    {
        return plugin_basename(WOOECUR_PLUGIN_ID . '/' . WOOECUR_PLUGIN_ID . '.php');
    }

    /**
     * Get plugin URL
     *
     * @param string $path
     * @return string
     */
    public static function getPluginUrl ($path = '')
    {
    	return WOOECUR_PLUGIN_URL . $path;
    }

    /**
     * Add plugin action links
     * @param array $links
     * @return array
     */
    public static function addPluginActionLinks (array $links)
    {
        $action_links = array(
            // Add link to global eCurring settings
            '<a href="' . self::getSettingsHelper()->getGlobalSettingsUrl() . '">' . __('eCurring settings', 'woo-ecurring') . '</a>',
        );

        // Add link to log files viewer for WooCommerce >= 2.2.0
        if (version_compare(self::getStatusHelper()->getWooCommerceVersion(), '2.2.0', ">="))
        {
            // Add link to WooCommerce logs
            $action_links[] = '<a href="' . self::getSettingsHelper()->getLogsUrl() . '">' . __('Logs', 'woo-ecurring') . '</a>';
        }

        return array_merge($action_links, $links);
    }

    /**
     * @return eCurring_WC_Helper_Settings
     */
    public static function getSettingsHelper ()
    {
        static $settings_helper;

        if (!$settings_helper)
        {
            $settings_helper = new eCurring_WC_Helper_Settings();
        }

        return $settings_helper;
    }

    /**
     * @return eCurring_WC_Helper_Api
     */
    public static function getApiHelper ()
    {
        static $api_helper;

        if (!$api_helper)
        {
            $api_helper = new eCurring_WC_Helper_Api(self::getSettingsHelper());
        }

        return $api_helper;
    }

    /**
     * @return eCurring_WC_Helper_Data
     */
    public static function getDataHelper ()
    {
        static $data_helper;

        if (!$data_helper)
        {
            $data_helper = new eCurring_WC_Helper_Data(self::getApiHelper());
        }

        return $data_helper;
    }

    /**
     * @return eCurring_WC_Helper_Status
     */
    public static function getStatusHelper ()
    {
        static $status_helper;

        if (!$status_helper)
        {
            $status_helper = new eCurring_WC_Helper_Status();
        }

        return $status_helper;
    }

	/**
	 * @return eCurring_WC_Subscription
	 */
	public static function eCurringSubscription($subscription) {

		static $ecurring_subscription;

		if (!$ecurring_subscription)
		{
			$ecurring_subscription = new eCurring_WC_Subscription($subscription);
		}

		return $ecurring_subscription;
	}

	/**
	 * If an order is paid with another payment method (gateway) after a first payment was
	 * placed with eCurring, set a flag, so status updates (like expired) aren't processed by
	 * eCurring for WooCommerce.
	 */
	public static function setOrderPaidByOtherGateway( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {

			$ecurring_payment_id    = get_post_meta( $order_id, '_ecurring_payment_id', $single = true );
			$order_payment_method = get_post_meta( $order_id, '_payment_method', $single = true );

			if ( $ecurring_payment_id !== '' && ( strpos( $order_payment_method, 'ecurring' ) === false ) ) {
				update_post_meta( $order->id, '_ecurring_paid_by_other_gateway', '1' );
			}

		} else {

			$ecurring_payment_id    = $order->get_meta( '_ecurring_payment_id', $single = true );
			$order_payment_method = $order->get_payment_method();

			if ( $ecurring_payment_id !== '' && ( strpos( $order_payment_method, 'ecurring' ) === false ) ) {

				$order->update_meta_data( '_ecurring_paid_by_other_gateway', '1' );
				$order->save();
			}
		}

		return true;

	}

	/**
	 * Load eCurring scripts and styles
	 */
	public static function eCurringEnqueueScriptsAndStyles() {

		wp_enqueue_style( 'ecurring_frontend_style', eCurring_WC_Plugin::getPluginUrl( 'assets/css/ecurring.css' ), '', WOOECUR_PLUGIN_VERSION );
		wp_enqueue_script( 'ecurring_frontend_script', eCurring_WC_Plugin::getPluginUrl( 'assets/js/ecurring.js' ), array ( 'jquery' ), WOOECUR_PLUGIN_VERSION );

		// Used for status description tooltips in My Account > eCurring Subscriptions
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		// Cancel a subscription dialog in My Account > eCurring Subscriptions
		wp_enqueue_script( 'woo-ecurring-sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@8', array (), WOOECUR_PLUGIN_VERSION );

		// Make sure we can run AJAX on the site for My Account > eCurring Subscriptions
		wp_localize_script( 'ecurring_frontend_script', 'woo_ecurring_ajax',
			array ( 'ajax_url' => admin_url( 'admin-ajax.php' ),
			        'are_you_sure_short' => __( 'Are you sure?', 'woo-ecurring' ),
			        'are_you_sure_long' => __( 'Are you sure you want to cancel this subscription?', 'woo-ecurring' ),
			        'yes_cancel_it' => __( 'Yes, cancel it!', 'woo-ecurring' ),
			        'no_dont_cancel_it' => __( 'No, do not cancel it!', 'woo-ecurring' ),
			        'cancelled' => __( 'Cancelled', 'woo-ecurring' ),
			        'your_subscription' => __( 'Your subscription #', 'woo-ecurring' ),
			        'is_cancelled' => __( ' is cancelled!', 'woo-ecurring' ),
			        'cancel_failed' => __( 'Cancel failed', 'woo-ecurring' ),
			        'is_not_cancelled' => __( ' is cancelled!', 'woo-ecurring' ),
                ) );

	}

	/**
	 * Load eCurring admin scripts and styles
	 */
	public static function eCurringEnqueueScriptsAndStylesAdmin() {
		wp_enqueue_script( 'ecurring_admin_script', eCurring_WC_Plugin::getPluginUrl( 'assets/js/admin.js' ), array ( 'jquery' ), WOOECUR_PLUGIN_VERSION );

		wp_localize_script( 'ecurring_admin_script', 'woo_ecurring_admin_text',
			array (
				'manual_order_notice'   => __( 'Do not add eCurring products to manual orders: subscriptions and recurring orders will not be created!', 'woo-ecurring' ),
			) );

	}

	/**
	 * Adding eCurring tab to the WC Product
	 *
	 * @param $product_data_tabs
	 *
	 * @return mixed
	 */
	public static function eCurringProductDataTab($product_data_tabs) {
		$product_data_tabs['woo-ecurring-tab'] = array(
			'label'  => __('eCurring', 'woo-ecurring'),
			'target' => 'woo_ecurring_product_data',
		);

		return $product_data_tabs;
	}

	/**
	 * Adding list of products to the eCurring tab
	 */
	public static function eCurringProductDataFields() {
		global $post;

		$api                         = eCurring_WC_Plugin::getApiHelper();
		$page_size = 50;
		$subscription_plans_response = json_decode($api->apiCall('GET', 'https://api.ecurring.com/subscription-plans?page[size]='.$page_size), true);
		$subscription_plans_data = isset($subscription_plans_response['data']) ? $subscription_plans_response['data'] : false;
		if (!$subscription_plans_data) {
			exit;
		}

		$subscription_plans    = [];
		$subscription_plans[0] = '- Select product -';
		foreach ($subscription_plans_data as $subscription_plan) {
			if ($subscription_plan['attributes']['status'] == 'active' && $subscription_plan['attributes']['mandate_authentication_method'] == 'first_payment' ) {
				$subscription_plans[ $subscription_plan['id'] ] = $subscription_plan['attributes']['name'];
			}
		}

		if ($subscription_plans_response['links']['next']) {

			$last_page_link = parse_url($subscription_plans_response['links']['last']);
			parse_str($last_page_link['query'], $query);
			$last_page_num = $query['page']['number'];

			if ($last_page_num > 1) {

				for ($i = 2; $i<=$last_page_num; $i++) {
					$next_page_response = json_decode($api->apiCall('GET','https://api.ecurring.com/subscription-plans?page[number]='.$i.'&page[size]='.$page_size),true);

					if (isset($next_page_response['data'])) {
						foreach ($next_page_response['data'] as $subscription_plan) {
							if ($subscription_plan['attributes']['status'] == 'active' && $subscription_plan['attributes']['mandate_authentication_method'] == 'first_payment' ) {
								$subscription_plans[ $subscription_plan['id'] ] = $subscription_plan['attributes']['name'];
							}
						}
					}
				}
			}
		}
		?>
		<div id="woo_ecurring_product_data" class="panel woocommerce_options_panel">

            <div style="padding: 15px;">
				<?php
				echo __('You are adding an eCurring product. The eCurring product determines the price your customers will pay when purchasing this product. Make sure the product price in WooCommerce exactly matches the eCurring product price. Important: the eCurring product determines the price your customers will pay when purchasing this product. Make sure the product price in WooCommerce exactly matches the eCurring product price. The eCurring product price should include all shipping cost. Any additional shipping costs added by WooCommerce will not be charged.', 'woo-ecurring');
				?>
            </div>
			<?php
			woocommerce_wp_select(array(
				'id'            => '_woo_ecurring_product_data',
				'wrapper_class' => 'show_if_simple',
				'label'         => __('Product', 'woo-ecurring'),
				'description'   => __('', 'woo-ecurring'),
				'options'       => $subscription_plans,
				'value'         => get_post_meta($post->ID, '_ecurring_subscription_plan', true)
			));
			?>
		</div>
		<?php
	}

	/**
	 * Save eCurring product in the product post meta - "_ecurring_subscription_plan"
	 *
	 * @param $post_id
	 */
	public static function eCurringProcessProductMetaFieldsSave($post_id) {

	    if (isset($_POST['_woo_ecurring_product_data'])) {
			update_post_meta($post_id, '_ecurring_subscription_plan', $_POST['_woo_ecurring_product_data']);
		}
	}

	/**
	 * Empty cart if adding eCurring subscription product.
	 * Or if adding not eCurring product to cart, where already is eCurring product
	 *
	 * @param $cart_item_data
	 * @param $product_id
	 * @param $variation_id
	 *
	 * @return mixed
	 */
	public static function eCurringCartUpdate($cart_item_data, $product_id, $variation_id) {
		$items = WC()->cart->get_cart();

		$subscription_plan_id = get_post_meta($product_id, '_ecurring_subscription_plan', true);
		foreach ($items as $item) {
			if (get_post_meta($item['product_id'], '_ecurring_subscription_plan', true)) {
				WC()->cart->empty_cart();
			}
		}

		if ($subscription_plan_id) {
			WC()->cart->empty_cart();
		}

		return $cart_item_data;
	}

	/**
	 * Hide coupon in cart and checkout if there is eCurring product
	 *
	 * @param $enabled
	 *
	 * @return bool
	 */
	public static function eCurringHideCouponField($enabled) {
		if (is_checkout() || is_cart()) {
			$items = WC()->cart->get_cart();
			foreach ($items as $item) {
				if (get_post_meta($item['product_id'], '_ecurring_subscription_plan', true)) {
					$enabled = false;
				}
			}
		}

		return $enabled;
	}

	/**
	 * eCurring WC Coupon notice show
	 */
	public static function eCurringCouponNotice() {
		if (isset($_GET['post_type']) && $_GET['post_type'] == 'shop_coupon' && !get_option('ecurring_dismiss_coupon_disabled')) {
			$class   = 'notice notice-warning is-dismissible ecurring-coupon-disabled';
			$message = __('You have eCurring installed and enabled, please note that coupons do not work with eCurring and will be removed from the checkout!', 'woo-ecurring');

			printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
		}
	}

	/**
	 * eCurring WC Product edit page notice
	 */
	public static function eCurringAdminProductPageNotice() {

		if ( ! is_admin() ) {
			return;
		}

		global $post;

		if ( ! isset( $post ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( $screen->post_type == 'product' && $screen->base == 'post' && get_post_meta( $post->ID, '_ecurring_subscription_plan', true ) ) {
			$class   = 'notice notice-info ecurring-product-notice';
			$message = __( 'This WooCommerce product is connected to a product in eCurring. Changes in WooCommerce are not synced 
			to eCurring, and changes in eCurring are not synced to WooCommerce. Any change to eCurring products or WooCommerce 
			products needs to manually be changed in the other system! It\'s recommended to select the checkbox at Inventory > Sold individually, because only one eCurring product can be purchased 
			at a time, and quantity selections will not be shown.', 'woo-ecurring' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}

	/**
	 * eCurring WC Coupon notice dismiss
	 */
	public static function eCurringDismissCouponDisabled() {
		update_option('ecurring_dismiss_coupon_disabled', '1');
		echo 'success';
		wp_die();
	}

	/**
	 * Left only eCurring payment gateway if there is eCurring product, and hide payment gateway div.
	 * Otherwise just exclude eCurring payment gateway.
	 *
	 * @param $gateway_list
	 *
	 * @return mixed
	 */
	public static function eCurringFilterGateways($gateway_list) {
		if ( isset( WC()->cart ) ) {
			$items = WC()->cart->get_cart();
			foreach ( $items as $item ) {
				if ( get_post_meta( $item['product_id'], '_ecurring_subscription_plan', true ) ) {
					foreach ( $gateway_list as $gateway => $data ) {
						if ( $gateway != 'ecurring_wc_gateway_ecurring' && ! self::isMolliePaymentGateway($data)) {
							unset( $gateway_list[ $gateway ] );
						}
					}
				} else {
					unset( $gateway_list['ecurring_wc_gateway_ecurring'] );
				}
			}
		}

		return $gateway_list;
	}

    /**
     * Check if provided payment gateway instance was added by Mollie.
     *
     * @param WC_Payment_Gateway $gateway
     *
     * @return bool Check result.
     */
	protected static function isMolliePaymentGateway(WC_Payment_Gateway $gateway)
    {
        return in_array(get_class($gateway), self::getMollieGatewaysClassNames(), true);
    }


    /**
     * Get list of payment gateways class names added by Mollie Payments for WooCommerce plugin.
     *
     * This method has to be static for now because it called from another static method.
     *
     * @return array
     */
    protected static function getMollieGatewaysClassNames()
    {
        if (class_exists(Mollie_WC_Plugin::class) &&
            isset(Mollie_WC_Plugin::$GATEWAYS) &&
            is_array(Mollie_WC_Plugin::$GATEWAYS)) {
            return Mollie_WC_Plugin::$GATEWAYS;
        }

        return [];
    }

	/**
	 * eCurring add to cart Ajax redirect
	 */
	public static function eCurringAddToCartRedirectAjax() {

	    $product_id = $_POST['product_id'];
	    $is_ajax = get_option('woocommerce_enable_ajax_add_to_cart');

        if (get_post_meta($product_id, '_ecurring_subscription_plan', true)) {

			wp_send_json(array('result' => 'success', 'url' => wc_get_checkout_url(),'is_ajax' => $is_ajax));
			wp_die();
        }
        else {
			wp_send_json(array('result' => 'error'));
			wp_die();
        }
    }

	/**
	 * eCurring add to cart redirect
	 */
    public static function eCurringRedirectToCheckout() {

		$checkout_url = wc_get_checkout_url();
		$cart_url = wc_get_cart_url();
		$current_url = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'/';

		if (isset($_REQUEST['add-to-cart'])) {

			$product_id = $_REQUEST['add-to-cart'];
			if (get_post_meta($product_id, '_ecurring_subscription_plan', true)) {
				return $checkout_url;
			}

			if (get_option('woocommerce_cart_redirect_after_add') == 'yes') {
				return $cart_url;
			}
			return $current_url;
		}

		return $cart_url;
    }

	/**
	 * Mandate accept description
	 */
	public static function eCurringCheckoutPrivacyPolicyText() {

		$items = WC()->cart->get_cart();
		foreach ($items as $item) {

		    $ecurring_subscription_plan = get_post_meta($item['product_id'], '_ecurring_subscription_plan', true);

			if ($ecurring_subscription_plan) {

				$api                         = eCurring_WC_Plugin::getApiHelper();
				$subscription_plans_response = json_decode($api->apiCall('GET', 'https://api.ecurring.com/subscription-plans/'.$ecurring_subscription_plan), true);
				$terms_link = isset($subscription_plans_response['data']) ? $subscription_plans_response['data']['attributes']['terms'] : false;
				$terms = $terms_link ? '<a href="'.esc_url($terms_link).'" target="_blank">terms & conditions</a>' : 'terms & conditions';

				echo '<style>.woocommerce-privacy-policy-text{display: none}</style>';
				echo '<div class="ecurring-mandate-accept"><input type="checkbox" name="mandate_accepted" id="mandate_accepted">';
				echo sprintf(__('I authorize %s to automatically debit future costs from my debit or credit card depending on the chosen payment method in the next step.', 'woo-ecurring'), esc_html(get_bloginfo('name')));
				echo '&nbsp;<span class="ecurring-mandate-accept-read-more">' . __('More info on direct debit mandate.', 'woo-ecurring');
				echo '<span class="accept-required">*</span></label>';
				echo '<span class="ecurring-mandate-accept-description"><span class="accept-required">*</span>';
                echo sprintf(__('You authorize %1$s to charge recurring payments from your account or card, commissioned by %1$s. If you do not agree with a charge, you can have it charged back. Please contact your bank within 8 weeks of the debit. Ask your bank about the terms and conditions. Benificiary ID: NL05ZZZ577987450000.', 'woo-ecurring'), esc_html(get_bloginfo('name')));
                echo '</span></span></div>';
			}
		}
	}

	/**
     * Disable WC sale price for eCurring products
     *
	 * @param $sale_price
	 * @param $product
	 *
	 * @return mixed
	 */
	public static function eCurringDisableSalePrice( $sale_price, $product ) {

		return get_post_meta($product->get_id(), '_ecurring_subscription_plan', true) ? $product->get_regular_price() : $sale_price;
	}


	/**
     * eCurring Add to cart button text
     *
	 * @param $text
	 * @param $product
	 *
	 * @return string
	 */
	public static function eCurringAddToCartText($text, $product) {

		return get_post_meta($product->get_id(), '_ecurring_subscription_plan', true) ? __('Subscribe', 'woo-ecurring') : __($text, 'woo-ecurring');
    }

	/**
     * eCurring add to cart button text
     *
	 * @param $default
	 * @param $product
	 *
	 * @return bool
	 */
	public static function eCurringDisableQuantity($default, $product) {

		return get_post_meta($product->get_id(), '_ecurring_subscription_plan', true) ? true : $default;
    }

	/**
	 * Don't show a "Pay" button in My account when payment method is eCurring
	 */
	public static function eCurringRemovePayFromMyAccountOrders( $actions, WC_Order $order ) {

		// Can't use $wp->request or is_wc_endpoint_url() to check if this code only runs on My Account,
		// because slugs/endpoints can be translated (with WPML) and other plugins.
		// So disabling on is_account_page (if not checkout, bug in WC) and $_GET['change_payment_method'] for now.

		// Do not disable if account page is also checkout (workaround for bug in WC), do disable on change payment method page (param)
		if ( ( ! is_checkout() && is_account_page() ) || ! empty( $_GET['change_payment_method'] ) ) {

			// Does the order have eCurring as payment method?
			if ( $order->get_payment_method() == 'ecurring_wc_gateway_ecurring' ) {

				// If pay actiuon is set, unset it
				if ( isset( $actions['pay'] ) ) {
					unset( $actions['pay'] );
				}
			}
		}

		return $actions;
	}

	/**
	 * Don't show eCurring payment method on Pay for order page
	 */
	public static function disableeCurringOnPayForOrder( $available_gateways ) {

		// Can't use $wp->request or is_wc_endpoint_url() to check page,
		// because slugs/endpoints can be translated (with WPML) and other plugins.
		// So disabling on is_account_page (if not checkout, bug in WC) and $_GET['pay_for_order'] for now.

			// Do not disable if account page is also checkout (workaround for bug in WC), do disable on change payment method page (param)
			if ( ( ! is_checkout() && is_account_page() ) || ! empty( $_GET['pay_for_order'] ) ) {
				foreach ( $available_gateways as $key => $value ) {
					if ( strpos( $key, 'ecurring_' ) !== false ) {
						unset( $available_gateways[ $key ] );
					}
				}
			}

		return $available_gateways;
	}

	/**
	 * eCurring Subscriptions - Add query vars
	 */
	public static function eCurringSubscriptionsQueryVars( $vars ) {
		$vars[] = 'ecurring-subscriptions';

		return $vars;
	}

	/**
	 * eCurring Subscriptions - Add to My account menu
	 *
	 * @param $items
	 *
	 * @return array
	 */
	public static function eCurringSubscriptionsMyAccount( $items ) {

		$new_items                           = array ();
		$new_items['ecurring-subscriptions'] = __( 'Subscriptions', 'woo-ecurring' );

		// Search for the item position and +1 since is after the selected item key.
		$position = array_search( 'orders', array_keys( $items ) ) + 1;

		// Insert the new item.
		$final_items = array_slice( $items, 0, $position, true );
		$final_items += $new_items;
		$final_items += array_slice( $items, $position, count( $items ) - $position, true );

		return $final_items;
	}

	/**
	 * eCurring Subscriptions - Update page title to "Subscriptions"
	 *
	 *
	 * @param $title
	 *
	 * @return string|void
	 */
	public static function eCurringSubscriptionsUpdateTitle( $title ) {
		global $wp_query;

		if ( isset( $wp_query->query_vars['ecurring-subscriptions'] ) && in_the_loop() ) {
			return __('Subscriptions', 'woo-ecurring');
		}

		return $title;
	}

	/**
	 * eCurring Subscriptions - Subscriptions table
	 */
	public static function eCurringSubscriptionsContent() {

		$api = eCurring_WC_Plugin::getApiHelper();

		// Handle basic caching for a users subscriptions
		if ( get_transient( 'ecurring_subscriptions_user_' . get_current_user_id()) !== false ) {

			$subscriptions = get_transient( 'ecurring_subscriptions_user_' . get_current_user_id() );

		} else {

			$ecurring_customer_id = get_user_meta( get_current_user_id(), 'ecurring_customer_id', true );

			$subscriptions = json_decode( $api->apiCall( 'GET', 'https://api.ecurring.com/customers/' . $ecurring_customer_id . '/subscriptions?page[size]=15' ), true );
			delete_transient( 'ecurring_subscriptions_user_' . get_current_user_id() );
			set_transient( 'ecurring_subscriptions_user_' . get_current_user_id(), $subscriptions, 5 * MINUTE_IN_SECONDS  );
		}

		echo '<div class="woocommerce_account_ecurring_subscriptions">';
		echo '<table class="shop_table shop_table_responsive my_account_ecurring_subscriptions my_account_orders">';
		echo '<tr>';
		echo '<th class="order-number">Subscription</th>';
		echo '<th class="">Product</th>';
		echo '<th class="">Status</th>';
		echo '<th class=""></th>';

		echo '</tr>';

		if ( ! isset( $subscriptions['data'] ) ) {
			echo "</tr>";
			echo '</table>';

			echo "No subscriptions found!";

			echo '</div>';

		} else {

		    // Sort on newest subscriptions first
			krsort($subscriptions['data']);

			foreach ( $subscriptions['data'] as $subscription ) {

				$subscription_plan_id   = $subscription['relationships']['subscription-plan']['data']['id'];
				$subscription_status    = $subscription['attributes']['status'];
				$subscription_plan_name = false;

				if ( get_transient( 'ecurring_subscription_plans') !== false ) {

					$subscription_plans     = get_transient( 'ecurring_subscription_plans' );
					$subscription_plan_name = ( isset ( $subscription_plans[ $subscription_plan_id ] ) ) ? $subscription_plans[ $subscription_plan_id ] : false;

				}

				if ( $subscription_plan_name == false ) {

					// Get the subscription plan
					$subscription_plan = json_decode( $api->apiCall( 'GET', 'https://api.ecurring.com/subscription-plans/' . $subscription_plan_id ), true );

					$subscription_plans = get_transient( 'ecurring_subscription_plans' );

					$subscription_plans[ $subscription_plan_id ] = $subscription_plan['data']['attributes']['name'];

					set_transient( 'ecurring_subscription_plans', $subscription_plans, DAY_IN_SECONDS );
				}

				switch ( $subscription_status ) {
					case 'active':
						$status_description = __( "This subscription is active. Orders and payments for this subscriptions are processed automatically every period.", 'woo-ecurring' );
						break;
					case 'cancelled':
						$status_description = __( "This subscription has been cancelled. Orders and payments won't be processed anymore, unless a payment has already been sent to the bank before the subscription was cancelled.", 'woo-ecurring' );
						break;
					case 'paused':
						$status_description = __( "This subscription is paused. Orders and payments won't be processed anymore, unless a payment has already been sent to the bank before the subscription was paused. Contact the shop to resume this subscription.", 'woo-ecurring' );
						break;
					case 'unverified':
						$status_description = __( "You haven't verified this subscription yet. Orders won't be processed until you do. Click on 'Activate' in the overview to activate your subscription.", 'woo-ecurring' );
						break;
				}

				echo "<tr class='order'>
		            <td class='order-number'>#" . $subscription['id'] . "</td>
		            <td class=''>" . $subscription_plan_name . "</td>
		            <td class='order-status' id='ecurring-status-subscription-" . $subscription['id'] . "'>" . ucwords( $subscription_status );

				echo "<div class='ecurring-subscriptions-status'><span class='dashicons dashicons-info ecurring-subscriptions-status-read-more ' style='float: right'>";
				echo '<span class="ecurring-subscriptions-status-description">' . $status_description . '</span></span></div>';

				echo "</td>";

				echo '<input type="hidden" id="subscription_id" name="subscription_id"
                                   value="' . $subscription['id'] . '">';

				if ( $subscription_status == 'unverified' ) {
					echo "<td class='order-actions'><a class='button' href='" . $subscription['attributes']['confirmation_page'] . "'>" . __('ACTIVATE', 'woo-ecurring') . "</a></td>";
				} elseif ( $subscription_status != 'cancelled' ) {
					echo "<td class='order-actions'><a class='button' id='ecurring_cancel_subscription_" . $subscription['id'] . "' onclick='canceleCurringSubscriptionWithID(this)' data-ecurring-subscription-id='" . $subscription['id'] . "'>" . __('CANCEL', 'woo-ecurring') . "</a></td>";
				} else {
					echo "<td class='order-actions'></td>";
				}

				echo "</tr>";
			}
			echo '</table>';
			echo '</div>';

		}

	}

	/**
	 * eCurring Subscriptions - Cancel Subscriptions redirect
	 */
	public static function eCurringSubscriptionsCancelSubscription() {

		$subscription_id = sanitize_text_field($_POST['subscription_id']);

		$api = eCurring_WC_Plugin::getApiHelper();

		$request = json_decode( $api->apiCall( 'PATCH', 'https://api.ecurring.com/subscriptions/' . $subscription_id, array (
			'data' => array (
				'type'       => 'subscription',
				'id'         => $subscription_id,
				'attributes' => array (
					'status' => 'cancelled',
				)
			),
		) ), true );

		$subscription_status = $request['data']['attributes']['status'];

		if ( $subscription_status == 'cancelled' ) {
		    // If a subscriptions status is successfully changed, remove the cache
			delete_transient( 'ecurring_subscriptions_user_' . get_current_user_id() );

			wp_send_json( array ( 'result' => 'success' ) );
			wp_die();
		} else {
			wp_send_json( array ( 'result' => 'failed' ) );
			wp_die();
		}

	}

	/**
	 * Add 'eCurring details' meta box
	 */
	public static function eCurringAddOrdersMetaBox() {
		add_meta_box( 'woo_ecurring_orders_metabox', __( 'eCurring details', 'woo-ecurring' ), array (
			__CLASS__,
			'eCurringAddOrdersMetaBoxCallback'
		), 'shop_order', 'side', 'core' );
	}

	/**
	 * eCurring details meta box callback
	 */
	public static function eCurringAddOrdersMetaBoxCallback() {
		global $post;

		if ( ! get_post_meta( $post->ID, '_ecurring_subscription_id', true ) ) {
			$subscription_id = get_post_meta( $post->ID, '_ecurring_subscription_relation', true );
		} else {
			$subscription_id = get_post_meta( $post->ID, '_ecurring_subscription_id', true );
		}

		if ( ! $subscription_id ) {
			echo __( 'No eCurring subscription found for this order.', 'woo-ecurring' );

			return;
		}

		// Load Helpers
		$api  = eCurring_WC_Plugin::getApiHelper();
		$data = eCurring_WC_Plugin::getDataHelper();

		$subscription = json_decode( $api->apiCall( 'GET', 'https://api.ecurring.com/subscriptions/' . $subscription_id ), true );

		$customer_id = $subscription['data']['relationships']['customer']['data']['id'];

		echo '<h2 style="padding: 8px 0px;">' . __( 'General details', 'woo-ecurring' ) . '</h2>';
		echo '<p style="padding-left: 15px; line-height: 25px;">';

		echo __( 'Subscription ID', 'woo-ecurring' ) . ': ' . $subscription_id . '<br />';
		echo __( 'Customer ID', 'woo-ecurring' ) . ': ' . $customer_id . '<br />';
		echo '</p>';

		echo '<h2 style="padding: 8px 0px;">' . __( 'Transaction details', 'woo-ecurring' ) . '</h2>';
		echo '<p style="padding-left: 15px; line-height: 25px;">';

		if ( ! get_post_meta( $post->ID, '_transaction_id', true ) ) {
			echo __( 'No known transaction yet.', 'woo-ecurring' );

			return;
		} else {
			$transaction_id = get_post_meta( $post->ID, '_transaction_id', true );

			$api         = eCurring_WC_Plugin::getApiHelper();
			$transaction = json_decode( $api->apiCall( 'GET', 'https://api.ecurring.com/transactions/' . $transaction_id ), true );

			echo __( 'ID', 'woo-ecurring' ) . ': <a href="https://app.ecurring.com/transactions/' . $transaction_id . '" target="_blank">' . $transaction_id . '</a><br />';
			echo __( 'Status', 'woo-ecurring' ) . ': ' . $data->geteCurringPrettyStatus( $transaction['data']['attributes']['status'] ) . '<br />';
			echo __( 'Amount', 'woo-ecurring' ) . ': ' . wc_price( $transaction['data']['attributes']['amount'] ) . '<br />';
			echo __( 'Method', 'woo-ecurring' ) . ': ' . $transaction['data']['attributes']['payment_method'] . '<br />';

		}
		echo '</p>';


	}

}

