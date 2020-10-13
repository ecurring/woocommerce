<?php

abstract class eCurring_WC_Gateway_Abstract extends WC_Payment_Gateway
{
	/**
	 * WooCommerce default statuses
	 */
	const STATUS_PENDING = 'pending';
	const STATUS_PROCESSING = 'processing';
	const STATUS_ON_HOLD = 'on-hold';
	const STATUS_COMPLETED = 'completed';
	const STATUS_CANCELLED = 'cancelled'; // Mollie uses canceled (US English spelling), WooCommerce and this plugin use cancelled.
	const STATUS_FAILED = 'failed';
	const STATUS_REFUNDED = 'refunded';

    /**
     * @var string
     */
    protected $default_title;

    /**
     * @var string
     */
    protected $default_description;

    /**
     * @var bool
     */
    protected $display_logo;

    /**
     *
     */
    public function __construct ()
    {
        // No plugin id, gateway id is unique enough
        $this->plugin_id    = '';
        // Use gateway class name as gateway id
        $this->id           = strtolower(get_class($this));
        // Set gateway title (visible in admin)
        $this->method_title = $this->getDefaultTitle();
        $this->method_description = $this->getSettingsDescription();

        $this->title        = $this->get_option('title');
        $this->display_logo = $this->get_option('display_logo') == 'yes';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_email_after_order_table', array($this, 'displayInstructions'), 10, 3);

	    // Adjust title and text on Order Received page in some cases, see issue #166
	    add_filter( 'the_title', array ( $this, 'onOrderReceivedTitle' ), 10, 2 );
	    add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'onOrderReceivedText'), 10, 2 );
    }

    /**
     * @param $order
     * @param $payment
     */
	protected function handlePaidOrderWebhook( $order, $payment ) {
		// Duplicate webhook call
		eCurring_WC_Plugin::setHttpResponseCode( 204 );

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_id = $order->id;
		} else {
			$order    = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order );
			$order_id = $order->get_id();
		}

		eCurring_WC_Plugin::debug( __METHOD__ . ' - ' . $this->id . ": Order $order_id does not need a payment by eCurring (payment {$payment->id}).", true );

	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public function getReturnRedirectUrlForOrder( WC_Order $order ) {
		// Get order ID in the correct way depending on WooCommerce version
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_id = $order->id;
		} else {
			$order_id = $order->get_id();
		}

		$api = eCurring_WC_Plugin::getApiHelper();
		$eCurring_subscription_id = get_post_meta($order_id,'_ecurring_subscription_id',true);

		$eCurring_subscription = json_decode($api->apiCall('GET','https://api.ecurring.com/subscriptions/'.$eCurring_subscription_id),true)['data'];

		$eCurring_subscription_attr = $eCurring_subscription['attributes'];
		$mandate_data = array(
			'ecurring_mandate_code' => isset($eCurring_subscription_attr['mandate_code']) ? $eCurring_subscription_attr['mandate_code'] : '',
			'ecurring_mandate_accepted' => $eCurring_subscription_attr['mandate_accepted'] === true ? '1' : '',
			'ecurring_mandate_accepted_date' => isset($eCurring_subscription_attr['mandate_accepted_date']) ? $eCurring_subscription_attr['mandate_accepted_date'] : ''
		);

		foreach ($mandate_data as $key => $value) {
			update_post_meta($order_id,$key,$value);
		}

		eCurring_WC_Plugin::debug( __METHOD__ . " $order_id: Determine what the redirect URL in WooCommerce should be." );

		return $this->get_return_url( $order );
	}

    /**
     * Add content to the WC emails.
     *
     * @param WC_Order $order
     * @param bool     $admin_instructions (default: false)
     * @param bool     $plain_text (default: false)
     * @return void
     */
    public function displayInstructions(WC_Order $order, $admin_instructions = false, $plain_text = false)
    {

	    if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		    $order_payment_method = $order->payment_method;
	    } else {
		    $order_payment_method = $order->get_payment_method();
	    }

        // Invalid gateway
        if ($this->id !== $order_payment_method)
        {
            return;
        }

	    if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		    $subscription = eCurring_WC_Plugin::getDataHelper()->getActiveSubscription($order->id);
	    } else {
			$subscription = eCurring_WC_Plugin::getDataHelper()->getActiveSubscription($order->get_id());
	    }

        // eCurring subscription not found or invalid gateway
        if (!$subscription)
        {
            return;
        }

        $instructions = $this->getInstructions($order, $subscription, $admin_instructions, $plain_text);

        if (!empty($instructions))
        {
            $instructions = wptexturize($instructions);

            if ($plain_text)
            {
                echo $instructions . PHP_EOL;
            }
            else
            {
                echo '<section class="woocommerce-order-details woocommerce-info ecurring-instructions" >';
                echo wpautop($instructions) . PHP_EOL;
                echo '</section>';
            }
        }
    }

    /**
     * @param WC_Order                  $order
     * @param  $subscription
     * @param bool                      $admin_instructions
     * @param bool                      $plain_text
     * @return string|null
     */
    protected function getInstructions (WC_Order $order,  $subscription, $admin_instructions, $plain_text)
    {

        // No definite payment status
		$subscription = eCurring_WC_Plugin::eCurringSubscription($subscription);

        if ($subscription->unverified() || $subscription->cancelled() || $subscription->paused())
        {
            if ($admin_instructions)
            {
                // Message to admin
                return __('We have not received a definite subscription status.', 'woo-ecurring');
            }
            else
            {
                // Message to customer
                return __('We have not received a definite subscription status. You will receive an email as soon as we receive a confirmation of the bank/merchant.', 'woo-ecurring');
            }
        }
        elseif ($subscription->active())
        {
            return sprintf(
            /* translators: Placeholder 1: payment method */
                __('Subscription completed with <strong>%s</strong>', 'woo-ecurring'),
                $this->get_title()
            );
        }

        return null;
    }

	/**
	 * @param WC_Order $order
	 */
	public function onOrderReceivedTitle( $title, $id = null ) {

		if ( eCurring_WC_Plugin::getDataHelper()->is_order_received_page() && get_the_ID() === $id ) {
			global $wp;

			$order = false;
			$order_id  = apply_filters( 'woocommerce_thankyou_order_id', absint( $wp->query_vars['order-received'] ) );
			$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : wc_clean( $_GET['key'] ) );
			if ( $order_id > 0 ) {
				$order = wc_get_order( $order_id );

				if ( ! is_a( $order, 'WC_Order' ) ) {
					return $title;
				}

				if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
					$order_key_db = $order->order_key;
				} else {
					$order_key_db = $order->get_order_key();
				}

				if ( $order_key_db != $order_key ) {
					$order = false;
				}
			}

			if ( $order == false){
				return $title;
			}

			$order = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order );

			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$order_payment_method = $order->payment_method;
			} else {
				$order_payment_method = $order->get_payment_method();
			}

			// Invalid gateway
			if ( $this->id !== $order_payment_method ) {
				return $title;
			}

			// Title for cancelled orders
			if ( $order->has_status( 'cancelled' ) ) {
				$title = __( 'Order cancelled', 'woo-ecurring' );

				return $title;
			}

			// Checks and title for pending/open orders
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$subscription = eCurring_WC_Plugin::getDataHelper()->getActiveSubscription( $order->id );
			} else {
				$subscription = eCurring_WC_Plugin::getDataHelper()->getActiveSubscription( $order->get_id() );
			}

			// eCurring payment not found or invalid gateway
			if ( ! $subscription ) {
				return $title;
			}

			$subscription = eCurring_WC_Plugin::eCurringSubscription($subscription);

			if ( $subscription->unverified() ) {

				// Add a message to log and order explaining a payment with status "unverified", only if it hasn't been added already
				if ( get_post_meta( $order_id, '_ecurring_unverified_status_note', true ) !== '1' ) {

					// Add message to log
					eCurring_WC_Plugin::debug( $this->id . ': Customer returned to store, but subscription still pending for order #' . $order_id . '. Status should be updated automatically in the future, if it doesn\'t this might indicate a communication issue between the site and eCurring.' );
					$subscription_link = $order->get_meta('_ecurring_subscription_link',true);
					// Add message to order as order note
					$order->add_order_note( sprintf(
					/* translators: Placeholder 1: payment method title, placeholder 2: payment ID */
						__( 'Payment still pending (%s) but customer already returned to the store. Status should be updated automatically in the future, if it doesn\'t this might indicate a communication issue between the site and eCurring.', 'woo-ecurring' ),
						'<a href="'.$subscription_link.'" target="_blank">'.$subscription->id.'</a>') );

					update_post_meta( $order_id, '_ecurring_unverified_status_note', '1' );
				}

				// Update the title on the Order received page to better communicate that the payment is pending.
				$title .= __( ', payment pending.', 'woo-ecurring' );

				return $title;
			}

		}

		return $title;

	}

	/**
	 * @param WC_Order $order
	 */
	public function onOrderReceivedText( $text, $order ) {
		if ( !is_a( $order, 'WC_Order' ) ) {
			return $text;
		}

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_payment_method = $order->payment_method;
		} else {
			$order_payment_method = $order->get_payment_method();
		}

		// Invalid gateway
		if ( $this->id !== $order_payment_method ) {
			return $text;
		}

		if ( $order->has_status( 'cancelled' ) ) {
			$text = __( 'Your order has been cancelled.', 'woo-ecurring' );

			return $text;
		}

		return $text;

	}

	/**
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	protected function orderNeedsPayment( WC_Order $order ) {

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_id = $order->id;
		} else {
			$order_id = $order->get_id();
		}
		// Check whether the order is processed and paid via another gateway
		if ( $this->isOrderPaidByOtherGateway( $order ) ) {
			eCurring_WC_Plugin::debug( $this->id . ': Order ' . $order_id . ' orderNeedsPayment check: no, processed by other (non-eCurring) gateway.', true );
			return false;
		}

		// Check whether the order is processed and paid via eCurring
		if ( ! $this->isOrderPaidAndProcessed( $order ) ) {
			eCurring_WC_Plugin::debug( $this->id . ': Order ' . $order_id . ' orderNeedsPayment check: yes, not processed by eCurring gateway.', true );
			return true;
		}

		if ( $order->needs_payment() ) {
			eCurring_WC_Plugin::debug( $this->id . ': Order ' . $order_id . ' orderNeedsPayment check: yes, WooCommerce thinks order needs payment.', true );
			return true;
		}

		// Has initial order status 'on-hold'
		if ( $this->getInitialOrderStatus($order) === self::STATUS_ON_HOLD && eCurring_WC_Plugin::getDataHelper()->hasOrderStatus( $order, self::STATUS_ON_HOLD ) ) {
			eCurring_WC_Plugin::debug( $this->id . ': Order ' . $order_id . ' orderNeedsPayment check: yes, has status On-Hold. ', true );
			return true;
		}

		return false;
	}

	/**
	 * @param WC_Order $order
	 * @return bool|string
	 */
	protected function getInitialOrderStatus (WC_Order $order) {
		$initial_order = $order->get_parent_id();
		return $initial_order != 0 ? wc_get_order($initial_order)->get_status() : false;
	}

	/**
	 * Check if any multi language plugins are enabled and return the correct site url.
	 *
	 * @return string
	 */
	protected function getSiteUrlWithLanguage() {
		/**
		 * function is_plugin_active() is not available. Lets include it to use it.
		 */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$site_url          = get_site_url();
		$polylang_fallback = false;

		if ( is_plugin_active( 'polylang/polylang.php' ) || is_plugin_active( 'polylang-pro/polylang.php' ) ) {

			$lang = PLL()->model->get_language( pll_current_language() );

			if ( empty ( $lang->search_url ) ) {
				$polylang_fallback = true;
			} else {
				$polylang_url = $lang->search_url;
				$site_url     = str_replace( $site_url, $polylang_url, $site_url );
			}
		}

		if ( $polylang_fallback == true || is_plugin_active( 'mlang/mlang.php' ) || is_plugin_active( 'mlanguage/mlanguage.php' ) ) {

			$slug = get_bloginfo( 'language' );
			$pos  = strpos( $slug, '-' );
			if ( $pos !== false ) {
				$slug = substr( $slug, 0, $pos );
			}
			$slug     = '/' . $slug;
			$site_url = str_replace( $site_url, $site_url . $slug, $site_url );

		}

		return $site_url;
	}


	/**
	 * @return bool
	 */
	protected function setOrderPaidAndProcessed( WC_Order $order ) {

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_id = $order->id;
			update_post_meta( $order_id, '_ecurring_paid_and_processed', '1' );
		} else {
			$order->update_meta_data( '_ecurring_paid_and_processed', '1' );
			$order->save();
		}
		return true;
	}


	/**
	 * @return bool
	 */
	protected function isOrderPaidAndProcessed( WC_Order $order ) {

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_id           = $order->id;
			$paid_and_processed = get_post_meta( $order_id, '_ecurring_paid_and_processed', $single = true );
		} else {
			$paid_and_processed = $order->get_meta( '_ecurring_paid_and_processed', true );
		}

		return $paid_and_processed;

	}

	/**
	 * @return bool
	 */
	protected function isOrderPaidByOtherGateway( WC_Order $order ) {

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_id           = $order->id;
			$paid_by_other_gateway = get_post_meta( $order_id, '_ecurring_paid_by_other_gateway', $single = true );
		} else {
			$paid_by_other_gateway = $order->get_meta( '_ecurring_paid_by_other_gateway', true );
		}

		return $paid_by_other_gateway;

	}

	/**
	 * @return bool
	 */
	protected function isOrderPaymentStartedByOtherGateway( WC_Order $order ) {

		$order_id = $order->get_id();

		// Get the current payment method id for the order
		$payment_method_id = get_post_meta( $order_id, '_payment_method', $single = true );

		// If the current payment method id for the order is not eCurring, return true
		if ( ( strpos( $payment_method_id, 'ecurring' ) === false ) ) {

			return true;
		}

		return false;

	}


    /**
     * @return mixed
     */
    abstract public function getMethodId ();

    /**
     * @return string
     */
    abstract public function getDefaultTitle ();

    /**
     * @return string
     */
    abstract protected function getSettingsDescription ();

    /**
     * @return string
     */
    abstract protected function getDefaultDescription ();

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order
	 *
	 * @return string
	 */
	public function get_transaction_url( $order ) {

		if (is_numeric($order->get_transaction_id())) {
			$this->view_transaction_url = $order->get_meta('_ecurring_subscription_link',true);
		}
		else $this->view_transaction_url = 'https://app.ecurring.com/transactions/%s';

		return parent::get_transaction_url( $order );
	}

}
