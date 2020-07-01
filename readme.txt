=== WooCommerce eCurring gateway ===
Contributors: davdebcom, inpsyde
Tags: recurring payments, woocommerce, payment gateway, direct debit, subscriptions, woocommerce subscriptions, sepa
Requires at least: 4.6
Tested up to: 5.3
Stable tag: 1.2.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Collect your subscription fees in WooCommerce with ease.

== Description ==

eCurring is specialised in the processing of subscription payments with the use of SEPA Direct Debit and credit card. Periodic collection of payments, also referred to as recurring payments, has grown exponentially the last few years. However it is often a challenge for companies, to build this process in-house, resulting in long development cycles and high costs.

With eCurring you can easily manage customers and subscriptions, collect funds periodically and automate follow-ups in a single solution. No technical knowledge is required to use eCurring. Start managing and collecting your subscriptions through eCurring today.

= PAYMENT METHODS =

* iDEAL & SEPA - Direct Debit

* VISA
* MasterCard
* American Express
* V Pay
* Maestro

Please go to the [signup page](https://app.ecurring.com/signup?utm_medium=partner&utm_source=woocommerce&utm_campaign=shop%20description&utm_content=to%20pricing%20page) to create a new eCurring account and start receiving payments in a couple of minutes. Contact support@ecurring.com if you have any questions or comments about this plugin.

> Pricing: From €39 per month, plus variable (transaction) costs.

= FEATURES =

* Accept subscription payments without WooCommerce Subscriptions
* Fast in-house support. You will always be helped by someone who knows our products intimately.

== Frequently Asked Questions ==

= I can't install the plugin, the plugin is displayed incorrectly =

Please temporarily enable the [WordPress Debug Mode](https://codex.wordpress.org/Debugging_in_WordPress). Edit your `wp-config.php` and set the constants `WP_DEBUG` and `WP_DEBUG_LOG` to `true` and try
it again. When the plugin triggers an error, WordPress will log the error to the log file `/wp-content/debug.log`. Please check this file for errors. When done, don't forget to turn off
the WordPress debug mode by setting the two constants `WP_DEBUG` and `WP_DEBUG_LOG` back to `false`.

= I get a white screen when opening ... =

Most of the time a white screen means a PHP error. Because PHP won't show error messages on default for security reasons, the page is white. Please turn on the WordPress Debug Mode to turn on PHP error messages (see previous answer).

= The eCurring payment gateway isn't displayed in my checkout =

* Please go to WooCommerce > Settings > Checkout in your WordPress admin and scroll down to the eCurring settings section.
* Check which payment gateways are disabled.
* Go to the specific payment gateway settings page to find out why the payment gateway is disabled.

= The order status is not getting updated after successfully completing the payment =

* Please check the eCurring log file located in `/wp-content/uploads/wc-logs/` or `/wp-content/plugin/woocommerce/logs` for debug info. Please search for the correct order number and check if eCurring has called the shop Webhook to report the payment status.
* Do you have maintenance mode enabled? Please make sure to whitelist the 'wc-api' endpoint otherwise eCurring can't report the payment status to your website.
* Please check your eCurring dashboard to check if there are failed webhook reports. eCurring tried to report the payment status to your website but something went wrong.
* Contact support@ecurring.com with the order number. We can investigate the specific payment and check whether eCurring successfully reported the payment state to your webshop.

= Payment gateways and mails aren't always translated =

This plugin uses [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woo-ecurring) for translations. WordPress will automatically add those translations to your website if they hit 100% completion at least once. If you are not seeing the eCurring plugin as translated on your website, the plugin is probably not translated (completely) into your language (you can view the status on the above URL).

You can either download and use the incomplete translations or help us get the translation to 100% by translating it.

To download translations manually:
1. Go to [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woo-ecurring/)
2. Click on the percentage in the "Stable" column for your language.
3. Scroll down to "Export". 
4. Choose "All current" and "MO - Machine Object" 
5. Upload this file to plugins/languages/woo-ecurring/.
6. Repeat this for all your translations.

If you want to help translate the plugin, read the instructions in the [Translate strings instructions](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/#translating-strings).

= Can I add payment fees to payment methods? =

Yes, you can with a separate plugin. At the moment we have tested and can recommend [Payment Gateway Based Fees and Discounts for WooCommerce](https://wordpress.org/plugins/checkout-fees-for-woocommerce/). Other plugins might also work.

= Can I set up payment methods to show based on customers country? =

Yes, you can with a separate plugin. At the moment we have tested and can recommend [WooCommerce - Country Based Payments](https://wordpress.org/plugins/woocommerce-country-based-payments/). Other plugins might also work.

= Why do orders with payment method BankTransfer and Direct Debit get the status 'on-hold'? =

These payment methods take longer than a few hours to complete. The order status is set to 'on-hold' to prevent the WooCommerce setting 'Hold stock (minutes)' (https://docs.woothemes.com/document/configuring-woocommerce-settings/#inventory-options) will
cancel the order. The order stock is also reduced to reserve stock for these orders. The stock is restored if the payment fails or is cancelled. You can change the initial order status for these payment methods on their setting page.

= I have a different question about this plugin =

Please contact support@ecurring.com with your eCurring details ID, please describe your problem as detailed as possible. Include screenshots where appropriate.
Where possible, also include the eCurring log file. You can find the eCurring log files in `/wp-content/uploads/wc-logs/` or `/wp-content/plugin/woocommerce/logs`.

== Screenshots ==

1. The global eCurring settings.
2. Change the title and description for every payment gateway. Some gateways have special options.
3. The available payment gateways in the checkout.
4. The order received page will display the payment status and customer details if available.
5. The order received page for the gateway bank transfer will display payment instructions.
6. Some payment methods support refunds. The 'Refund' button will be available when the payment method supports refunds.

== Installation ==

= Minimum Requirements =

* PHP version 5.6 or greater
* PHP extensions enabled: cURL, JSON
* WordPress 4.6 or greater
* WooCommerce 3.0 or greater

= Automatic installation =

1. Install the plugin via Plugins -> New plugin. Search for 'WooCommerce eCurring gateway'.
2. Activate the 'eCurring for WooCommerce' plugin through the 'Plugins' menu in WordPress
3. Set your eCurring API key at WooCommerce -> Settings -> Checkout (or use the *eCurring Settings* link in the Plugins overview)
4. You're done, the active payment methods should be visible in the checkout of your webshop.

= Manual installation =

1. Unpack the download package
2. Upload the directory 'woo-ecurring' to the `/wp-content/plugins/` directory
3. Activate the 'eCurring for WooCommerce' plugin through the 'Plugins' menu in WordPress
4. Set your eCurring API key at WooCommerce -> Settings -> Checkout (or use the *eCurring Settings* link in the Plugins overview)
5. You're done, the active payment methods should be visible in the checkout of your webshop.

Please contact support@ecurring.com if you need help installing the eCurring WooCommerce plugin. Please provide your eCurring details and website URL.

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==

= 1.2.0 - 01-07-2020 =

* Add more customer communication languages.
* Fix add inline CSS using `wp_add_inline_style`.

= 1.1.4 - 25-02-2020 =

* Allow disconnect a connected eCurring product.
* Do not send empty attributes to customers endpoint.

= 1.1.3 - 09-12-2019 =

* Add business name from order billing on customer creation.
* Handle `WP_Error` from eCurring api call response.

= 1.1.2 - 31-10-2019 =

* Add address information from order billing on customer creation.
* Fix translation sprintf issue by adding positional parameter.

= 1.1.1 - 17-09-2019 =

* Fix translation strings.

= 1.1.0 - 29-05-2019 =

* Add WooCommerce > Account > Subscriptions for eCurring subscriptions.

= 1.0.5 - 03-04-2019 =

* Update statuses in ecurring_webhook to use new eCurring pretty status.
* Disable eCurring on "Pay for order" page.
* Convert eCurring API status to a pretty status for merchants and users.
* Remove 'Pay' button from My Account when method is eCurring.
* Add warning to "Add products" when products are added to manual order.
* Update checkout "Accept mandate" to use a tooltip.
* Updating mapping of order statuses.
* Add custom order status 'wc-ecurring-retrying-payment'.
* Correct WooCommerce status to on-hold.

= 1.0.4 - 20-02-2019 =

* Removed WooCommerce order status update in getReturnRedirectUrlForOrder.
* Removed default status "pending" in webhook function.

= 1.0.3 - 18-02-2019 =

* Private beta release.

= 1.0.2 - 11-02-2019 =

* Private beta release.

= 1.0.1 - 28-01-2019 =

* Private beta release.

= 1.0.0 - 15-01-2019 =

* Private beta release.
