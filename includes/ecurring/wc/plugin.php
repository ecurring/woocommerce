<?php

use Brain\Nonces\WpNonce;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\ViewFactory;
use Dhii\Output\PhpEvaluator\FilePhpEvaluatorFactory;
use Dhii\Output\Template\PhpTemplate\FilePathTemplateFactory;
use Ecurring\WooEcurring\AdminPages\AdminController;
use Ecurring\WooEcurring\AdminPages\Form\FormFieldsCollectionBuilder;
use Ecurring\WooEcurring\AdminPages\Form\NonceFieldBuilder;
use Ecurring\WooEcurring\AdminPages\OrderEditPageController;
use Ecurring\WooEcurring\AdminPages\ProductEditPageController;
use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Api\SubscriptionPlans;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Customer\CustomerCrud;
use Ecurring\WooEcurring\EventListener\PaymentCompletedEventListener;
use Ecurring\WooEcurring\EventListener\AddToCartValidationEventListener;
use Ecurring\WooEcurring\PaymentGatewaysFilter\WhitelistedRecurringPaymentGatewaysFilter;
use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\EventListener\MollieMandateCreatedEventListener;
use Ecurring\WooEcurring\Settings\SettingsCrud;
use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateFactory;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusFactory;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcher;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactory;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Template\SettingsFormTemplate;
use Ecurring\WooEcurring\Template\SimpleTemplateBlockFactory;

// Require Webhook functions
require_once dirname(dirname(dirname(__FILE__))) . '/webhook_functions.php';

class eCurring_WC_Plugin
{

    /**
     * @var bool
     */
    private static $initiated = false;

    /**
     * Initialize plugin
     */
    public static function init()
    {

        if (self::$initiated) {
            /*
             * Already initialized
             */
            return;
        }

        $pluginBasename = self::getPluginFile();
        $settingsHelper = self::getSettingsHelper();
        $customerCrud = new CustomerCrud();

        $apiClient = new ApiClient($settingsHelper->getApiKey());
        $subscriptionMandateFactory = new SubscriptionMandateFactory();
        $subscriptionStatusFactory = new SubscriptionStatusFactory();
        $subscriptionFactory = new DataBasedSubscriptionFactory(
            $subscriptionMandateFactory,
            $subscriptionStatusFactory
        );

        $repository = new Repository($subscriptionFactory, self::getCustomersApiClient());

        $subscriptionsApiClient = new Subscriptions(self::getApiHelper(), $apiClient, $subscriptionFactory);
        $subscriptionsSwitcher = new SubscriptionStatusSwitcher($subscriptionsApiClient, $repository);

        (new MollieMandateCreatedEventListener($apiClient, $subscriptionsApiClient, $repository, $customerCrud))->init();
        (new AddToCartValidationEventListener())->init();
        (new PaymentCompletedEventListener($apiClient, $subscriptionsApiClient, $customerCrud, $subscriptionsSwitcher, $repository))->init();

        add_action('admin_init', static function () use ($settingsHelper, $repository) {
            $elementFactory = new ElementFactory();
            $wcBasedSettingsTemplate = new SettingsFormTemplate();
            $settingsFormAction = 'mollie-subscriptions-settings-form-submit';
            $nonceAction = 'mollie-subscriptions-settings-nonce-action';
            $nonce = new WpNonce($nonceAction);
            $settingsCrud = new SettingsCrud();
            $formConfig = (require WOOECUR_PLUGIN_DIR . 'includes/settings_form_fields.php')(
                $settingsFormAction,
                $settingsCrud
            );
            $viewFactory = new ViewFactory();

            $subscriptionPlans = new SubscriptionPlans(self::getApiHelper());

            $formBuilder = new FormFieldsCollectionBuilder($elementFactory, $viewFactory, $formConfig);
            $nonceFieldBuilder = new NonceFieldBuilder($elementFactory, $viewFactory);
            $simpleTemplateBlockFactory = new SimpleTemplateBlockFactory();
            $filePathTemplateFactory = new FilePathTemplateFactory(
                new FilePhpEvaluatorFactory(),
                [],
                []
            );

            $adminTemplatesPath = plugin_dir_path(WOOECUR_PLUGIN_FILE) . 'views/admin';

            $productEditPageController = new ProductEditPageController(
                $filePathTemplateFactory,
                $simpleTemplateBlockFactory,
                $subscriptionPlans,
                $adminTemplatesPath,
                ! empty($settingsHelper->getApiKey())
            );

            $orderEditPageController = new OrderEditPageController(
                $repository,
                $filePathTemplateFactory,
                $adminTemplatesPath
            );

            (new AdminController(
                $wcBasedSettingsTemplate,
                $formBuilder,
                $settingsCrud,
                $settingsFormAction,
                $nonce,
                $nonceFieldBuilder,
                $productEditPageController,
                $orderEditPageController
            )
            )->init();
        });

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . $pluginBasename, [ __CLASS__, 'addPluginActionLinks' ]);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [ __CLASS__, 'eCurringEnqueueScriptsAndStyles' ]);

        // admin scripts and styles
        add_action('admin_enqueue_scripts', [ __CLASS__, 'eCurringEnqueueScriptsAndStylesAdmin' ]);

        // Hide coupon in cart and checkout if there is eCurring product
        add_filter('woocommerce_coupons_enabled', [ __CLASS__, 'eCurringHideCouponField']);

        // eCurring WC Coupon notice show
        add_action('admin_notices', [ __CLASS__, 'eCurringCouponNotice']);

        // eCurring WC Product edit page notice
        add_action('admin_notices', [ __CLASS__, 'eCurringAdminProductPageNotice'], 11);

        // eCurring WC Coupon notice dismiss
        add_action('wp_ajax_dismiss_coupon_disabled', [ __CLASS__, 'eCurringDismissCouponDisabled']);

        // Left only eCurring payment gateway if there is eCurring product, and hide payment gateway div.
        // Otherwise just exclude eCurring payment gateway.
        add_filter('woocommerce_available_payment_gateways', [ __CLASS__, 'eCurringFilterGateways'], 1);

        // eCurring add to cart Ajax redirect
        add_action('wp_ajax_ecurring_add_to_cart_redirect', [ __CLASS__, 'eCurringAddToCartRedirectAjax']);
        add_action('wp_ajax_nopriv_ecurring_add_to_cart_redirect', [ __CLASS__, 'eCurringAddToCartRedirectAjax']);

        // Redirect URL after successful adding to cart
        add_filter('woocommerce_add_to_cart_redirect', [ __CLASS__, 'eCurringRedirectToCheckout']);

        // Mandate accept description
        add_action('woocommerce_checkout_terms_and_conditions', [ __CLASS__, 'eCurringCheckoutPrivacyPolicyText'], 20);

        // Disable WC sale price for eCurring products
        add_filter('woocommerce_product_get_sale_price', [ __CLASS__, 'eCurringDisableSalePrice'], 50, 2);
        add_filter('woocommerce_product_get_price', [ __CLASS__, 'eCurringDisableSalePrice'], 50, 2);

        // eCurring add to cart button text
        add_filter('woocommerce_product_add_to_cart_text', [ __CLASS__, 'eCurringAddToCartText'], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [ __CLASS__, 'eCurringAddToCartText'], 10, 2);

        //Disable quantity input for subscription products
        add_filter('woocommerce_is_sold_individually', [ __CLASS__, 'eCurringDisableQuantity'], 10, 2);

        add_filter('mollie-payments-for-woocommerce_is_subscription_payment', [__CLASS__, 'eCurringSubscriptionIsInCart']);

        // Mark plugin initiated
        self::$initiated = true;
    }

    /**
     * Log messages to WooCommerce log
     *
     * @param mixed $message
     * @param bool  $set_debug_header Set X-eCurring-Debug header (default false)
     */
    public static function debug($message, $set_debug_header = false)
    {
        // Convert message to string
        if (!is_string($message)) {
            $message = wc_print_r($message, true);
        }

        // Set debug header
        if ($set_debug_header && PHP_SAPI !== 'cli' && !headers_sent()) {
            header("X-eCurring-Debug: $message");
        }

        // Log message
        if (self::getSettingsHelper()->isDebugEnabled()) {
            $logger = wc_get_logger();

            $context =  [ 'source' => WOOECUR_PLUGIN_ID . '-' . date('Y-m-d') ];

            $logger->debug($message, $context);
        }
    }

    /**
     * Get location of main plugin file
     *
     * @return string
     */
    public static function getPluginFile()
    {
        return plugin_basename(WOOECUR_PLUGIN_ID . '/' . WOOECUR_PLUGIN_ID . '.php');
    }

    /**
     * Get plugin URL
     *
     * @param string $path
     * @return string
     */
    public static function getPluginUrl($path = '')
    {
        return WOOECUR_PLUGIN_URL . $path;
    }

    /**
     * Add plugin action links
     * @param array $links
     * @return array
     */
    public static function addPluginActionLinks(array $links)
    {
        $action_links = [
            // Add link to global eCurring settings
            '<a href="' . self::getSettingsHelper()->getGlobalSettingsUrl() . '">' . __('eCurring settings', 'woo-ecurring') . '</a>',
        ];

    // Add link to WooCommerce logs
        $action_links[] = '<a href="' . self::getSettingsHelper()->getLogsUrl() . '">' . __('Logs', 'woo-ecurring') . '</a>';

        return array_merge($action_links, $links);
    }

    /**
     * @return eCurring_WC_Helper_Settings
     */
    public static function getSettingsHelper()
    {
        static $settings_helper;

        if (!$settings_helper) {
            $settings_helper = new eCurring_WC_Helper_Settings();
        }

        return $settings_helper;
    }

    /**
     * @return eCurring_WC_Helper_Api
     */
    public static function getApiHelper()
    {
        static $api_helper;

        if (!$api_helper) {
            $api_helper = new eCurring_WC_Helper_Api(self::getSettingsHelper());
        }

        return $api_helper;
    }

    /**
     * @return eCurring_WC_Helper_Data
     */
    public static function getDataHelper()
    {
        static $data_helper;

        if (!$data_helper) {
            $data_helper = new eCurring_WC_Helper_Data(self::getApiHelper());
        }

        return $data_helper;
    }

    /**
     * @return eCurring_WC_Helper_Status
     */
    public static function getStatusHelper()
    {
        static $status_helper;

        if (!$status_helper) {
            $status_helper = new eCurring_WC_Helper_Status();
        }

        return $status_helper;
    }

    public static function getApiClient(): ApiClientInterface
    {
        static $apiClient;

        if (! $apiClient) {
            $apiClient = new ApiClient(self::getSettingsHelper()->getApiKey() ?? '');
        }

        return $apiClient;
    }

    public static function getSubscriptionRepository(): Repository
    {
        static $repository;

        if (! $repository) {
            $repository = new Repository(
                self::getSubscriptionsFactory(),
                self::getCustomersApiClient()
            );
        }

        return $repository;
    }

    public static function getSubscriptionsFactory(): DataBasedSubscriptionFactoryInterface
    {
        static $factory;

        if (! $factory) {
            $mandateFactory = new SubscriptionMandateFactory();
            $statusFactory = new SubscriptionStatusFactory();

            $factory = new DataBasedSubscriptionFactory($mandateFactory, $statusFactory);
        }

        return $factory;
    }

    public static function getCustomersApiClient(): Customers
    {
        static $customersApiClient;

        if (!$customersApiClient) {
            $customersApiClient = new Customers(self::getApiHelper(), self::getApiClient());
        }

        return $customersApiClient;
    }

    /**
     * Load eCurring scripts and styles
     */
    public static function eCurringEnqueueScriptsAndStyles()
    {

        wp_enqueue_style('ecurring_frontend_style', eCurring_WC_Plugin::getPluginUrl('assets/css/ecurring.css'), '', WOOECUR_PLUGIN_VERSION);
        wp_enqueue_script('ecurring_frontend_script', eCurring_WC_Plugin::getPluginUrl('assets/js/ecurring.js'), [ 'jquery' ], WOOECUR_PLUGIN_VERSION);

        // Used for status description tooltips in My Account > eCurring Subscriptions
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

        // Cancel a subscription dialog in My Account > eCurring Subscriptions
        wp_enqueue_script('woo-ecurring-sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@8', [], WOOECUR_PLUGIN_VERSION);

        // Make sure we can run AJAX on the site for My Account > eCurring Subscriptions
        wp_localize_script(
            'ecurring_frontend_script',
            'woo_ecurring_ajax',
            [ 'ajax_url' => admin_url('admin-ajax.php'),
                    'are_you_sure_short' => __('Are you sure?', 'woo-ecurring'),
                    'are_you_sure_long' => __('Are you sure you want to cancel this subscription?', 'woo-ecurring'),
                    'yes_cancel_it' => __('Yes, cancel it!', 'woo-ecurring'),
                    'no_dont_cancel_it' => __('No, do not cancel it!', 'woo-ecurring'),
                    'cancelled' => __('Cancelled', 'woo-ecurring'),
                    'your_subscription' => __('Your subscription #', 'woo-ecurring'),
                    'is_cancelled' => __(' is cancelled!', 'woo-ecurring'),
                    'cancel_failed' => __('Cancel failed', 'woo-ecurring'),
                    'is_not_cancelled' => __(' is cancelled!', 'woo-ecurring'),
             ]
        );
    }

    /**
     * Load eCurring admin scripts and styles
     */
    public static function eCurringEnqueueScriptsAndStylesAdmin()
    {

        wp_enqueue_script('ecurring_admin_script', eCurring_WC_Plugin::getPluginUrl('assets/js/admin.js'), [ 'jquery' ], WOOECUR_PLUGIN_VERSION);

        wp_localize_script(
            'ecurring_admin_script',
            'woo_ecurring_admin_text',
            [
                'manual_order_notice' => __('Do not add eCurring products to manual orders: subscriptions and recurring orders will not be created!', 'woo-ecurring'),
             ]
        );
    }

    /**
     * Hide coupon in cart and checkout if there is eCurring product
     *
     * @param $enabled
     *
     * @return bool
     */
    public static function eCurringHideCouponField($enabled)
    {

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
    public static function eCurringCouponNotice()
    {

        if (isset($_GET['post_type']) && $_GET['post_type'] == 'shop_coupon' && !get_option('ecurring_dismiss_coupon_disabled')) {
            $class = 'notice notice-warning is-dismissible ecurring-coupon-disabled';
            $message = __('You have eCurring installed and enabled, please note that coupons do not work with eCurring and will be removed from the checkout!', 'woo-ecurring');

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        }
    }

    /**
     * eCurring WC Product edit page notice
     */
    public static function eCurringAdminProductPageNotice()
    {

        if (! is_admin()) {
            return;
        }

        global $post;

        if (! isset($post)) {
            return;
        }

        $screen = get_current_screen();

        if ($screen->post_type == 'product' && $screen->base == 'post' && get_post_meta($post->ID, '_ecurring_subscription_plan', true)) {
            $class = 'notice notice-info ecurring-product-notice';
            $message = __('This WooCommerce product is connected to a product in eCurring. Changes in WooCommerce are not synced 
			to eCurring, and changes in eCurring are not synced to WooCommerce. Any change to eCurring products or WooCommerce 
			products needs to manually be changed in the other system! It\'s recommended to select the checkbox at Inventory > Sold individually, because only one eCurring product can be purchased 
			at a time, and quantity selections will not be shown.', 'woo-ecurring');

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        }
    }

    /**
     * eCurring WC Coupon notice dismiss
     */
    public static function eCurringDismissCouponDisabled()
    {

        update_option('ecurring_dismiss_coupon_disabled', '1');
        echo 'success';
        wp_die();
    }

    /**
     * If subscription product is in the cart, allow only gateways coming not from Mollie plugin.
     *
     * @param WC_Payment_Gateway[] $gatewayList Payment gateways from WooCommerce to filter.
     *
     * @return WC_Payment_Gateway[] Filtered gateways list.
     */
    public static function eCurringFilterGateways($gatewayList)
    {

        if (! self::eCurringSubscriptionIsInCart()) {
            return $gatewayList;
        }

        $mollieGateways = apply_filters('mollie-payments-for-woocommerce_retrieve_payment_gateways', []);
        $mollieRecurringGatewaysFilter = new WhitelistedRecurringPaymentGatewaysFilter($mollieGateways);
        return $mollieRecurringGatewaysFilter->filter($gatewayList);
    }

    /**
     * Check if cart contains at least one eCurring subscription product.
     *
     * @return bool Is subscription found.
     */
    public static function eCurringSubscriptionIsInCart()
    {

        if (isset(WC()->cart)) {
            $items = WC()->cart->get_cart();
            foreach ($items as $item) {
                $product = $item['data'];

                // we need to use !empty check instead of just meta_exists because
                // previously this field was added to non-eCurring products
                // with value '0'.
                if ($product instanceof WC_Product && ! empty($product->get_meta('_ecurring_subscription_plan', true))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * eCurring add to cart Ajax redirect
     */
    public static function eCurringAddToCartRedirectAjax()
    {

        $product_id = $_POST['product_id'];
        $is_ajax = get_option('woocommerce_enable_ajax_add_to_cart');

        if (get_post_meta($product_id, '_ecurring_subscription_plan', true)) {
            wp_send_json(['result' => 'success', 'url' => wc_get_checkout_url(), 'is_ajax' => $is_ajax]);
            wp_die();
        } else {
            wp_send_json(['result' => 'error']);
            wp_die();
        }
    }

    /**
     * eCurring add to cart redirect
     */
    public static function eCurringRedirectToCheckout()
    {

        $checkout_url = wc_get_checkout_url();
        $cart_url = wc_get_cart_url();
        $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '/';

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
    public static function eCurringCheckoutPrivacyPolicyText()
    {

        $items = WC()->cart->get_cart();
        foreach ($items as $item) {
            $ecurring_subscription_plan = get_post_meta($item['product_id'], '_ecurring_subscription_plan', true);

            if ($ecurring_subscription_plan) {
                $api = eCurring_WC_Plugin::getApiHelper();
                $subscription_plans_response = json_decode($api->apiCall('GET', 'https://api.ecurring.com/subscription-plans/' . $ecurring_subscription_plan), true);
                $terms_link = isset($subscription_plans_response['data']) ? $subscription_plans_response['data']['attributes']['terms'] : false;
                $terms = $terms_link ? '<a href="' . esc_url($terms_link) . '" target="_blank">terms & conditions</a>' : 'terms & conditions';

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
    public static function eCurringDisableSalePrice($sale_price, $product)
    {

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
    public static function eCurringAddToCartText($text, $product)
    {

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
    public static function eCurringDisableQuantity($default, $product)
    {

        return get_post_meta($product->get_id(), '_ecurring_subscription_plan', true) ? true : $default;
    }
}
