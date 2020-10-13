<?php

class eCurring_WC_Helper_Data {
	/**
	 * Transient prefix. We can not use plugin slug because this
	 * will generate to long keys for the wp_options table.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'woo-ecurring-';

	/**
	 * @var eCurring_WC_Helper_Api
	 */
	protected $api_helper;

	/**
	 * @param eCurring_WC_Helper_Api $api_helper
	 */
	public function __construct( eCurring_WC_Helper_Api $api_helper ) {
		$this->api_helper = $api_helper;
	}

	/**
	 * Get current locale
	 *
	 * @return string
	 */
	protected function getCurrentLocale() {
		return apply_filters( 'wpml_current_language', get_locale() );
	}

	/**
	 * @param string $transient
	 *
	 * @return string
	 */
	public function getTransientId( $transient ) {
		global $wp_version;

		/*
		 * WordPress will save two options to wp_options table:
		 * 1. _transient_<transient_id>
		 * 2. _transient_timeout_<transient_id>
		 */
		$transient_id       = self::TRANSIENT_PREFIX . $transient;
		$option_name        = '_transient_timeout_' . $transient_id;
		$option_name_length = strlen( $option_name );

		$max_option_name_length = 191;

		/**
		 * Prior to WordPress version 4.4.0, the maximum length for wp_options.option_name is 64 characters.
		 * @see https://core.trac.wordpress.org/changeset/34030
		 */
		if ( $wp_version < '4.4.0' ) {
			$max_option_name_length = 64;
		}

		if ( $option_name_length > $max_option_name_length ) {
			trigger_error( "Transient id $transient_id is to long. Option name $option_name ($option_name_length) will be to long for database column wp_options.option_name which is varchar($max_option_name_length).", E_USER_WARNING );
		}

		return $transient_id;
	}
	/**
	 * Convert eCurring api status to pretty status (for merchants and users)
	 *
	 */
	public function geteCurringPrettyStatus( $status ) {

		$pretty_status = '';

		switch ( $status ) {
			case 'succeeded':
				$pretty_status = __( 'Pending', 'woo-ecurring' );
				break;
			case 'rescheduled':
				$pretty_status = __( 'Rescheduled', 'woo-ecurring' );
				break;
			case 'fulfilled':
				$pretty_status = __( 'Paid', 'woo-ecurring' );
				break;
			case 'charged_back':
				$pretty_status = __( 'Charged back', 'woo-ecurring' );
				break;
			case 'payment_reminder_overdue':
				$pretty_status = __( 'Payment overdue', 'woo-ecurring' );
				break;
			case 'payment_failed':
				$pretty_status = __( 'Failed', 'woo-ecurring' );
				break;
			case 'failed':
				$pretty_status = __( 'Rejected by Mollie', 'woo-ecurring' );
				break;
			case 'payment_reminder_scheduled':
				$pretty_status = __( 'Payment reminder scheduled', 'woo-ecurring' );
				break;
			case 'payment_reminder_sent':
				$pretty_status = __( 'Payment reminder sent', 'woo-ecurring' );
				break;
			case 'wc-ecurring-retry':
				$pretty_status = __( 'Retrying payment at eCurring', 'woo-ecurring' );
				break;
		}

		return $pretty_status;
	}

	/**
	 * Get WooCommerce order
	 *
	 * @param int $order_id Order ID
	 *
	 * @return WC_Order|bool
	 */
	public function getWcOrder( $order_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			/**
			 * @since WooCommerce 2.2
			 */
			return wc_get_order( $order_id );
		}

		$order = new WC_Order();

		if ( $order->get_order( $order_id ) ) {
			return $order;
		}

		return false;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public function getOrderStatus( WC_Order $order ) {
		if ( method_exists( $order, 'get_status' ) ) {
			/**
			 * @since WooCommerce 2.2
			 */
			return $order->get_status();
		}

		return $order->status;
	}

	/**
	 * Check if a order has a status
	 *
	 * @param string|string[] $status
	 *
	 * @return bool
	 */
	public function hasOrderStatus( WC_Order $order, $status ) {
		if ( method_exists( $order, 'has_status' ) ) {
			/**
			 * @since WooCommerce 2.2
			 */
			return $order->has_status( $status );
		}

		if ( ! is_array( $status ) ) {
			$status = array ( $status );
		}

		return in_array( $this->getOrderStatus( $order ), $status );
	}

	/**
	 * Get eCurring subscription from cache or load from eCurring
	 * Skip cache by setting $use_cache to false
	 *
	 * @param string $subscription_id
	 * @param bool $use_cache (default: true)
	 *
	 * @return array|bool|null
	 */
	public function getSubscription( $subscription_id, $use_cache = true ) {
		try {
			$api = $this->api_helper;
			$response = json_decode($api->apiCall('GET','https://api.ecurring.com/subscriptions/'.$subscription_id),true);

			if (isset($response['data'])) {
				return $response['data'];
			}
			else {
				if(isset($response['errors'])) {
					eCurring_WC_Plugin::debug( __FUNCTION__ . ": Error log: " .$response['errors'] );
				}
				return false;
			}
		}
		catch ( Exception $e ) {
			eCurring_WC_Plugin::debug( __FUNCTION__ . ": Could not load payment $subscription_id: " . $e->getMessage() . ' (' . get_class( $e ) . ')' );
		}

		return null;
	}

	/**
	 * Get eCurring customer
	 *
	 * @param $customer_id
	 *
	 * @return array|bool|null
	 */
	public function getCustomer( $customer_id ) {
		try {
			$api = $this->api_helper;
			$response = json_decode($api->apiCall('GET','https://api.ecurring.com/customers/'.$customer_id),true);

			if (isset($response['data'])) {
				return $response['data'];
			}
			else {
				if(isset($response['errors'])) {
					eCurring_WC_Plugin::debug( __FUNCTION__ . ": Error log: " .$response['errors'] );
				}
				return false;
			}
		}
		catch ( Exception $e ) {
			eCurring_WC_Plugin::debug( __FUNCTION__ . ": Could not get customer $customer_id: " . $e->getMessage() . ' (' . get_class( $e ) . ')' );
		}

		return null;
	}

	/**
	 * Save active eCurring payment id for order
	 *
	 * @param int     $order_id
	 * @param object| $payment
	 *
	 * @return $this
	 */
	public function setActiveeCurringPayment( $order_id, $payment ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			update_post_meta( $order_id, '_ecurring_payment_id', $payment->id, $single = true );

			delete_post_meta( $order_id, '_ecurring_cancelled_payment_id' );

			if ( $payment->customerId ) {
				update_post_meta( $order_id, '_ecurring_customer_id', $payment->customerId, $single = true );
			}

		} else {
			$order = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );

			$order->update_meta_data( '_ecurring_payment_id', $payment->id );

			$order->delete_meta_data( '_ecurring_cancelled_payment_id' );

			if ( $payment->customerId ) {
				$order->update_meta_data( '_ecurring_customer_id', $payment->customerId );
			}

			$order->save();
		}

		return $this;
	}

	/**
	 * @param $order WC_Order
	 * @param $user_id
	 * @param $customer_id
	 *
	 * @return $this
	 */
	public function setUserCustomerId( $order, $user_id, $customer_id ) {
		if ( ! empty( $customer_id ) ) {

			try {

				if ( $user_id != 0 ) {
					$customer = new WC_Customer( $user_id );
					$customer->update_meta_data( 'ecurring_customer_id', $customer_id );
					$customer->save();
					eCurring_WC_Plugin::debug( __FUNCTION__ . ": Stored eCurring customer ID with WordPress user " . $user_id );
				}

				$order->update_meta_data( 'ecurring_customer_id', $customer_id );
				$order->save();
				eCurring_WC_Plugin::debug( __FUNCTION__ . ": Stored eCurring customer ID with WooCommerce order " . $order->get_id() );

			}
			catch ( Exception $e ) {
				eCurring_WC_Plugin::debug( __FUNCTION__ . ": Couldn't load (and save) WooCommerce customer based on user ID " . $user_id );

			}

		}

		return $this;
	}

	/**
	 * @param $order WC_Order
	 *
	 * @return mixed|string
	 * @throws \Exception
	 */
	public function getUsereCurringCustomerId( WC_Order $order) {

		$user_id = ( version_compare( WC_VERSION, '3.0', '<' ) ) ? $order->customer_user : $order->get_customer_id();

        $api = $this->api_helper;

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$customer_id = get_user_meta( $user_id, 'ecurring_customer_id', $single = true );
		} else {
			$customer    = new WC_Customer( $user_id );
			$customer_id = $customer->get_meta( 'ecurring_customer_id' );
		}

		// If there is a eCurring Customer ID set,
		// check that customer ID is valid for this API key and update the customer at eCurring
		if ( ! empty( $customer_id ) ) {

			try {
                $api->apiCall('GET','https://api.ecurring.com/customers/'.$customer_id);

                $request = $api->apiCall('PATCH',
                    'https://api.ecurring.com/customers/' . $customer_id, array(
					'data' => array(
						'type' => 'customer',
						'id' => $customer_id,
						'attributes' => $this->customerAttributesFromOrder($order)
					),
				));

				$response = json_decode($request,true);

				// Check if update worked or received an error
				if ( isset( $response['errors'] ) ) {
					eCurring_WC_Plugin::debug( 'Updating customer ' . $customer_id . ' failed: ' . print_r($response['errors'], TRUE ) );
					return $customer_id;
				}

				$customer_id = $response['data']['id'];

				eCurring_WC_Plugin::debug( __FUNCTION__ . ": Updated a eCurring Customer ($customer_id) for WordPress user with ID $user_id." );

			}
			catch ( Exception $e ) {
				eCurring_WC_Plugin::debug( __FUNCTION__ . ": eCurring Customer ID ($customer_id) not valid for user $user_id on this API key, try to create a new one." );
				$customer_id = '';
			}
		}

		// If there is no eCurring Customer ID set, try to create a new eCurring Customer
		if ( empty( $customer_id ) ) {
			try {

                $request = $api->apiCall('POST', 'https://api.ecurring.com/customers', array(
                    'data' => array(
                        'type' => 'customer',
                        'attributes' => $this->customerAttributesFromOrder($order)
                    ),
                ));

                $response = json_decode($request,true);

				// Check if update worked or received an error
				if ( !isset( $response['data']['id'] ) ) {
					eCurring_WC_Plugin::debug( 'Failed creating a eCurring customer for WooCommerce user ' . $user_id . ': ' . print_r($response['errors'], TRUE ) );
					return false;
				}

				// Check if update worked or received an error
				if ( isset( $response['errors'] ) ) {
					eCurring_WC_Plugin::debug( 'Failed creating a eCurring customer for WooCommerce user ' . $user_id );
					return false;
				}

                $customer_id = $response['data']['id'];

				$this->setUserCustomerId( $order, $user_id, $customer_id );

				eCurring_WC_Plugin::debug( __FUNCTION__ . ": Created a eCurring Customer ($customer_id) for WordPress user with ID $user_id. ID 0 means it's a guest user without an account." );

				return $customer_id;

			}
			catch ( Exception $e ) {
				eCurring_WC_Plugin::debug( __FUNCTION__ . ": Could not create eCurring Customer for WordPress user with ID $user_id. ID 0 means it's a guest user without an account. Error: " . $e->getMessage() . ' (' . get_class( $e ) . ')' );
			}
		} else {
			eCurring_WC_Plugin::debug( __FUNCTION__ . ": eCurring Customer ID ($customer_id) found and valid for this API key." );
		}

		return $customer_id;
	}

	/**
	 * Delete active eCurring payment id for order
	 *
	 * @param int    $order_id
	 * @param string $payment_id
	 *
	 * @return $this
	 */
	public function unsetActivePayment( $order_id, $payment_id = null ) {

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {

			// Only remove eCurring payment details if they belong to this payment, not when a new payment was already placed
			$ecurring_payment_id = get_post_meta( $order_id, '_ecurring_payment_id', $single = true );

			if ( $ecurring_payment_id == $payment_id ) {
				delete_post_meta( $order_id, '_ecurring_payment_id' );
			}
		} else {

			// Only remove eCurring payment details if they belong to this payment, not when a new payment was already placed
			$order               = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );
			$ecurring_payment_id = $order->get_meta( '_ecurring_payment_id', true );

			if ( $ecurring_payment_id == $payment_id ) {
				$order->delete_meta_data( '_ecurring_payment_id' );
				$order->save();
			}
		}

		return $this;
	}

	/**
	 * Get active eCurring payment id for order
	 *
	 * @param int $order_id
	 *
	 * @return string
	 */
	public function getActiveSubscriptionId( $order_id ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$ecurring_payment_id = get_post_meta( $order_id, '_ecurring_subscription_id', $single = true );
		} else {
			$order               = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );
			$ecurring_payment_id = $order->get_meta( '_ecurring_subscription_id', true );
		}

		return $ecurring_payment_id;
	}

	/**
	 * @param int $order_id
	 * @param bool $use_cache
	 *
	 * @return bool|null |null
	 */
	public function getActiveSubscription( $order_id, $use_cache = true ) {
		if ( $this->hasActiveSubscription( $order_id ) ) {
			return $this->getSubscription(
				$this->getActiveSubscriptionId( $order_id ),
				$use_cache
			);
		}

		return null;
	}

	/**
	 * Check if the order has an active eCurring payment
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function hasActiveSubscription( $order_id ) {
		$ecurring_payment_id = $this->getActiveSubscriptionId( $order_id );

		return ! empty( $ecurring_payment_id );
	}

	/**
	 * @param int    $order_id
	 * @param string $payment_id
	 *
	 * @return $this
	 */
	public function setCancelledPaymentId( $order_id, $payment_id ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			add_post_meta( $order_id, '_ecurring_cancelled_payment_id', $payment_id, $single = true );
		} else {
			$order = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );
			$order->update_meta_data( '_ecurring_cancelled_payment_id', $payment_id );
			$order->save();
		}

		return $this;
	}

	/**
	 * @param int $order_id
	 *
	 * @return null
	 */
	public function unsetCancelledPaymentId( $order_id ) {

		// If this order contains a cancelled (previous) payment, remove it.
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$ecurring_cancelled_payment_id = get_post_meta( $order_id, '_ecurring_cancelled_payment_id', $single = true );

			if ( ! empty( $ecurring_cancelled_payment_id ) ) {
				delete_post_meta( $order_id, '_ecurring_cancelled_payment_id' );
			}
		} else {

			$order                         = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );
			$ecurring_cancelled_payment_id = $order->get_meta( '_ecurring_cancelled_payment_id', true );

			if ( ! empty( $ecurring_cancelled_payment_id ) ) {
				$order = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );
				$order->delete_meta_data( '_ecurring_cancelled_payment_id' );
				$order->save();
			}
		}

		return null;
	}

	/**
	 * @param int $order_id
	 *
	 * @return string|false
	 */
	public function getCancelledPaymentId( $order_id ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$ecurring_cancelled_payment_id = get_post_meta( $order_id, '_ecurring_cancelled_payment_id', $single = true );
		} else {
			$order                         = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );
			$ecurring_cancelled_payment_id = $order->get_meta( '_ecurring_cancelled_payment_id', true );
		}

		return $ecurring_cancelled_payment_id;
	}

	/**
	 * Check if the order has been cancelled
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	public function hasCancelledPayment( $order_id ) {
		$cancelled_payment_id = $this->getCancelledPaymentId( $order_id );

		return ! empty( $cancelled_payment_id );
	}

	/**
	 * @param WC_Order $order
	 */
	public function restoreOrderStock( WC_Order $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( $item['product_id'] > 0 ) {
				$product = ( version_compare( WC_VERSION, '3.0', '<' ) ) ? $order->get_product_from_item( $item ) : $item->get_product();

				if ( $product && $product->exists() && $product->managing_stock() ) {
					$old_stock = ( version_compare( WC_VERSION, '3.0', '<' ) ) ? $product->stock : $product->get_stock_quantity();

					$qty = apply_filters( 'woocommerce_order_item_quantity', $item['qty'], $order, $item );

					$new_quantity = ( version_compare( WC_VERSION, '3.0', '<' ) ) ? $product->increase_stock( $qty ) : wc_update_product_stock( $product, $qty, 'increase' );

					do_action( 'woocommerce_auto_stock_restored', $product, $item );

					$order->add_order_note( sprintf(
						__( 'Item #%s stock incremented from %s to %s.', 'woocommerce' ),
						$item['product_id'],
						$old_stock,
						$new_quantity
					) );

					if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
						$order->send_stock_notifications( $product, $new_quantity, $item['qty'] );
					}
				}
			}
		}

		// Mark order stock as not-reduced
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			delete_post_meta( $order->id, '_order_stock_reduced' );
		} else {
			$order->delete_meta_data( '_order_stock_reduced' );
			$order->save();
		}
	}

	/**
	 * Check if the current page is the order received page
	 *
	 * @since WooCommerce 2.3.3
	 * @return bool
	 */
    public function is_order_received_page() {
		global $wp;

		return ( is_page(wc_get_page_id('checkout')) && isset($wp->query_vars['order-received']) ) ? true : false;
	}

	public function wc_date_format() {
		return apply_filters('woocommerce_date_format', get_option('date_format'));
	}

    /**
     * @param WC_Order $order
     * @return array
     */
    private function customerAttributesFromOrder(WC_Order $order)
    {
        $fields = [
            'first_name' => trim($order->get_billing_first_name()),
            'last_name' => trim($order->get_billing_last_name()),
            'company_name' => trim($order->get_billing_company()),
            'email' => trim($order->get_billing_email()),
            'telephone' => trim($order->get_billing_phone()),
            'city' => trim($order->get_billing_city()),
            'country_iso2' => trim($order->get_billing_country()),
            'street' => trim($order->get_billing_address_1()),
            'house_number' => trim($order->get_billing_address_2()),
            'postalcode' => trim($order->get_billing_postcode()),
            'language' => $this->getCustomerLanguage($order),
        ];

        $attributes = [];
        foreach ($fields as $key => $value) {
            if (isset($value) && $value !== '') {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Returns the preferred communication language of the customer.
     * @param WC_Order $order
     * @return string
     */
    protected function getCustomerLanguage(WC_Order $order)
    {
        $userId = (version_compare(WC_VERSION, '3.0', '<'))
            ? $order->customer_user
            : $order->get_customer_id();

        if (isset($userId)) {
            $userLocale = get_user_locale($userId);

            if ($userLocale === 'nl_BE') {
                return 'nl-be';
            }

            $language = explode('_', $userLocale);

            $eCurringAvailableLanguages = ['nl', 'en', 'fr', 'de'];
            if (in_array($language[0], $eCurringAvailableLanguages)) {
                return $language[0];
            }
        }

        return 'en';
    }

}
