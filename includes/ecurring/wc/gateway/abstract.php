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

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option('title');
        $this->display_logo = $this->get_option('display_logo') == 'yes';

        $this->_initDescription();

        if(!has_action('woocommerce_thankyou_' . $this->id)) {
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_email_after_order_table', array($this, 'displayInstructions'), 10, 3);

	    // Adjust title and text on Order Received page in some cases, see issue #166
	    add_filter( 'the_title', array ( $this, 'onOrderReceivedTitle' ), 10, 2 );
	    add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'onOrderReceivedText'), 10, 2 );

	    /* Override show issuers dropdown? */
	    if ( $this->get_option( 'issuers_dropdown_shown', 'yes' ) == 'no' ) {
		    $this->has_fields = false;
	    }

        if (!$this->isValidForUse())
        {
            // Disable gateway if it's not valid for use
            $this->enabled = false;
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
		$settings = eCurring_WC_Plugin::getSettingsHelper();
		$debug_desc = __('Log plugin events.', 'woo-ecurring');

		// For WooCommerce 2.2.0+ display view logs link
		if (version_compare(eCurring_WC_Plugin::getStatusHelper()->getWooCommerceVersion(), '2.2.0', ">="))
		{
			$debug_desc .= ' <a href="' . $settings->getLogsUrl() . '">' . __('View logs', 'woo-ecurring') . '</a>';
		}
		else
		{
			/* translators: Placeholder 1: Location of the log files */
			$debug_desc .= ' ' . sprintf(__('Log files are saved to <code>%s</code>', 'woo-ecurring'), defined('WC_LOG_DIR') ? WC_LOG_DIR : WC()->plugin_path() . '/logs/');
		}

		$api_key_setting = array(
			'title'             => __('API key', 'woo-ecurring'),
			'default'           => get_option('woo-ecurring_live_api_key'),
			'type'              => 'text',
			'description'              => sprintf(
			/* translators: Placeholder 1: API key mode (live or test). The surrounding %s's Will be replaced by a link to the eCurring profile */
				__('The API key is used to connect to eCurring. You can find your <strong>%s</strong> API key in your %seCurring dashboard%s under Settings > My Account > API tab. The API key is at least 40 characters and does not further contain any special characters.', 'woo-ecurring'),
				'live',
				'<a href="https://app.ecurring.com/dashboard" target="_blank">',
				'</a>'
			),
			'css'               => 'width: 350px',
			'placeholder'       => $live_placeholder = __('API key', 'woo-ecurring'),
			'custom_attributes' => array(
				'placeholder' => $live_placeholder,
				'pattern'     => '^\w{40,}$',
			),
		);

		if (!$this->isValidApiKeyProvided()) {
			$this->form_fields = array(
				'api_key' => $api_key_setting
			);
		}
		else {
			$this->form_fields = array(
				'enabled' => array(
					'title'       => __('Enable/Disable', 'woo-ecurring'),
					'type'        => 'checkbox',
					'label'       => sprintf(__('Enable %s', 'woo-ecurring'), $this->getDefaultTitle()),
					'default'     => 'yes'
				),
				'api_key' => $api_key_setting,
				'order_status_cancelled_payments' => array(
					'title'   => __('Order status after cancelled payment', 'woo-ecurring'),
					'type'    => 'select',
					'options' => array(
						'pending'          => __('Pending', 'woocommerce'),
						'cancelled'     => __('Cancelled', 'woocommerce'),
					),
					'description'    => __('Status for orders when a payment is cancelled. Default: pending. Orders with status Pending can be paid with another payment method, customers can try again. Cancelled orders are final. Set this to Cancelled if you only have one payment method or don\'t want customers to re-try paying with a different payment method.', 'woo-ecurring'),
					'default' => 'pending',
				),
				'debug' => array(
					'title'   => __('Debug Log', 'woo-ecurring'),
					'type'    => 'checkbox',
					'description'    => $debug_desc,
					'default' => 'yes',
				),
			);
		}

		$ecurring_settings = get_option('ecurring_wc_gateway_ecurring_settings');
		$ecurring_settings['title'] = $this->getDefaultTitle();
		update_option('ecurring_wc_gateway_ecurring_settings', $ecurring_settings);

    }

    protected function _initDescription ()
    {
        $description = $this->get_option('description', '');

        $this->description = $description;
    }

    public function admin_options ()
    {
		$settings = eCurring_WC_Plugin::getSettingsHelper();
		echo $settings->getPluginStatus();
        if (!$this->enabled && count($this->errors))
        {
            echo '<div class="inline error"><p><strong>' . __('Gateway Disabled', 'woo-ecurring') . '</strong>: '
                . implode('<br/>', $this->errors)
                . '</p></div>';
        }

        parent::admin_options();
    }

    /**
     * Check if this gateway can be used
     *
     * @return bool
     */
    protected function isValidForUse()
    {

        if (!$this->isValidApiKeyProvided())
        {

            $this->errors[] = sprintf(
                /* translators: The surrounding %s's Will be replaced by a link to the global setting page */
                    __('No API key provided. Please %sset your eCurring API key%s first.', 'woo-ecurring'),
                    '<a href="#ecurring_wc_gateway_ecurring_api_key">',
                    '</a>'
                );

            return false;
        }

        if (!$this->isCurrencySupported())
        {
            $this->errors[] = __('Current shop currency is not supported by eCurring, it only supports EURO!', 'woo-ecurring');
            return false;
        }

        return true;
    }

	/**
	 * Check if the gateway is available for use
	 *
	 * @return bool
	 */
	public function is_available() {

		// In WooCommerce check if the gateway is available for use (WooCommerce settings)
		if ( $this->enabled != 'yes' ) {

			return false;
		}

		return true;
	}

	/**
	 * @param int $order_id
	 *
	 * @throws Exception
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = eCurring_WC_Plugin::getDataHelper()->getWcOrder( $order_id );

		if ( ! $order ) {
			eCurring_WC_Plugin::debug( $this->id . ': Could not process payment, order ' . $order_id . ' not found.' );

			eCurring_WC_Plugin::addNotice( sprintf( __( 'Could not load order %s', 'woo-ecurring' ), $order_id ), 'error' );

			return array ( 'result' => 'failure' );
		}

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			eCurring_WC_Plugin::debug( $this->id . ': Start process_payment for order ' . $order->id, true );
		} else {
			eCurring_WC_Plugin::debug( $this->id . ': Start process_payment for order ' . $order->get_id(), true );
		}

		$api             = eCurring_WC_Plugin::getApiHelper();

		try {
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				eCurring_WC_Plugin::debug( $this->id . ': Create payment for order ' . $order->id, true );
			} else {
				eCurring_WC_Plugin::debug( $this->id . ': Create payment for order ' . $order->get_id(), true );
			}

			$date = date( 'Y-m-d H:i:s' );

			$customer_id = eCurring_WC_Plugin::getDataHelper()->getUsereCurringCustomerId( $order );

			if ( $customer_id == false ) {
				eCurring_WC_Plugin::debug( 'Could not get a correct eCurring customer ID, creating subscription failed!');
				return array ( 'result' => 'failure' );
			}

			update_post_meta( $order_id, 'ecurring_woocommerce_mandate_accepted_date', $date );

			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product_id = $item->get_product_id();
			}

			$subscription_plan_id     = get_post_meta( $product_id, '_ecurring_subscription_plan', true );
			$subscription_webhook_url = add_query_arg( 'ecurring-webhook', 'subscription', home_url( '/' ) );
			$transaction_webhook_url  = add_query_arg( 'ecurring-webhook', 'transaction', home_url( '/' ) );
			$woocommerce_return_url   = $this->getReturnUrl( $order );

			$data = array (
				'data' => array (
					'type'       => 'subscription',
					'attributes' => array (
						'customer_id'              => $customer_id,
						'subscription_plan_id'     => $subscription_plan_id,
						'success_redirect_url'     => $woocommerce_return_url,
						'subscription_webhook_url' => $subscription_webhook_url,
						'transaction_webhook_url'  => $transaction_webhook_url,
						'confirmation_sent'        => 'true',
						'metadata'                 => array ( 'source' => 'woocommerce' )
					)
				)
			);

            // Create eCurring subscription with customer id.
            $data = apply_filters( 'woocommerce_' . $this->id . '_args', $data, $order );
            do_action( WOOECUR_PLUGIN_ID . '_create_payment', $data, $order );

            $subscription_id = get_post_meta($order_id, '_ecurring_subscription_id', true);

            if($subscription_id) {
                $subscription_data = $api->getSubscriptionById($subscription_id);
            } else {
                $raw_api_response = apply_filters(WOOECUR_PLUGIN_ID . '_raw_api_response', null);

                $subscription_data = $api->createSubscription($data);

                $this->updateOrderWithSubscriptionData($subscription_data, $order);

                /**
                 * This action was triggered here before with unparsed api response data, so we have to do the same.
                 * We getting raw response above via filter.
                 * It would be great idea to deprecate this action and add another one with parsed response as argument.
                 */
                do_action( WOOECUR_PLUGIN_ID . '_payment_created', $raw_api_response, $order );
            }

			$redirect = isset( $response['error'] ) ? '' : $subscription_data['data']['attributes']['confirmation_page'] . '?accepted=true';

			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				eCurring_WC_Plugin::debug( "For order " . $order->id . " redirect user to eCurring Checkout URL: " . $redirect );
			} else {
				eCurring_WC_Plugin::debug( "For order " . $order->get_id() . " redirect user to eCurring Checkout URL: " . $redirect );
			}

			return array (
				'result'   => 'success',
				'redirect' => $redirect,
			);
		}
		catch ( Exception $e ) {
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				eCurring_WC_Plugin::debug( $this->id . ': Failed to create payment for order ' . $order->id . ': ' . $e->getMessage() );
			} else {
				eCurring_WC_Plugin::debug( $this->id . ': Failed to create payment for order ' . $order->get_id() . ': ' . $e->getMessage() );
			}

			/* translators: Placeholder 1: Payment method title */
			$message = sprintf( __( 'Could not create %s payment.', 'woo-ecurring' ), $this->title );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$message .= ' ' . $e->getMessage();
			}

			eCurring_WC_Plugin::addNotice( $message, 'error' );
		}

		return array ( 'result' => 'failure' );
	}

	protected function updateOrderWithSubscriptionData(array $response, WC_Order $order)
    {
        $ecurring_subscription_id = $response['data']['id'];

        if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
            $order_id = $order->id;
        } else {
            $order_id = $order->get_id();
        }

        update_post_meta( $order_id, '_ecurring_subscription_id',  $ecurring_subscription_id);

        $confirmation_page = $response['data']['attributes']['confirmation_page'];
        $subscription_link = 'https://app.ecurring.com/subscriptions/'.explode('/',$confirmation_page)[5];
        update_post_meta( $order_id, '_ecurring_subscription_link', $subscription_link );

        eCurring_WC_Plugin::debug( $this->id . ': Subscription ' . $ecurring_subscription_id . ' created for order ' . $order_id );

        $order->add_order_note( sprintf(
        /* translators: Placeholder 1: Payment method title, placeholder 2: payment ID */
            __( 'Manual %s payment started for subscription ID %s.', 'woo-ecurring' ),
            $this->method_title,
            '<a href="'.$subscription_link.'" target="_blank">'. $ecurring_subscription_id .'</a>'
        ) );
    }

    /**
     * @param WC_Order $order
     * @param string $new_status
     * @param string $note
     * @param bool $restore_stock
     */
    public function updateOrderStatus (WC_Order $order, $new_status, $note = '', $restore_stock = true )
    {
        $order->update_status($new_status, $note);

	    if ( version_compare( WC_VERSION, '3.0', '<' ) ) {

		    switch ($new_status)
		    {
			    case self::STATUS_ON_HOLD:

				    if ( $restore_stock == true ) {
					    if ( ! get_post_meta( $order->id, '_order_stock_reduced', $single = true ) ) {
						    // Reduce order stock
						    $order->reduce_order_stock();

						    eCurring_WC_Plugin::debug( __METHOD__ . ":  Stock for order {$order->id} reduced." );
					    }
				    }

				    break;

			    case self::STATUS_PENDING:
			    case self::STATUS_FAILED:
			    case self::STATUS_CANCELLED:
				    if (get_post_meta($order->id, '_order_stock_reduced', $single = true))
				    {
					    // Restore order stock
					    eCurring_WC_Plugin::getDataHelper()->restoreOrderStock($order);

					    eCurring_WC_Plugin::debug(__METHOD__ . " Stock for order {$order->id} restored.");
				    }

				    break;
		    }

	    } else {

		    switch ($new_status)
		    {
			    case self::STATUS_ON_HOLD:

				    if ( $restore_stock == true ) {
					    if ( ! $order->get_meta( '_order_stock_reduced', true ) ) {
						    // Reduce order stock
						    wc_reduce_stock_levels( $order->get_id() );

						    eCurring_WC_Plugin::debug( __METHOD__ . ":  Stock for order {$order->get_id()} reduced." );
					    }
				    }

				    break;

			    case self::STATUS_PENDING:
			    case self::STATUS_FAILED:
			    case self::STATUS_CANCELLED:
				    if ( $order->get_meta( '_order_stock_reduced', true ) )
				    {
					    // Restore order stock
					    eCurring_WC_Plugin::getDataHelper()->restoreOrderStock($order);

					    eCurring_WC_Plugin::debug(__METHOD__ . " Stock for order {$order->get_id()} restored.");
				    }

				    break;
		    }

	    }
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

	public function eCurringMethodId() {
		return 'ecurring_wc_gateway_ecurring';
	}

    /**
     * @param $payment
     * @return string
     */
    protected function getPaymentMethodTitle($payment)
    {
        $paymentMethodTitle = '';
        if (isset($payment->method) && ($payment->method == $this->eCurringMethodId())){
            $paymentMethodTitle = $this->method_title;
        }
        return $paymentMethodTitle;
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
     * Output for the order received page.
     */
    public function thankyou_page ($order_id)
    {
        $order = eCurring_WC_Plugin::getDataHelper()->getWcOrder($order_id);

        // Order not found
        if (!$order)
        {
            return;
        }

        // Empty cart
        if (WC()->cart) {
            WC()->cart->empty_cart();
        }

        // Same as email instructions, just run that
        $this->displayInstructions($order, $admin_instructions = false, $plain_text = false);
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
     * @param WC_Order $order
     * @return string
     */
    protected function getReturnUrl (WC_Order $order)
    {
        $site_url   = get_site_url();

	    $return_url = WC()->api_request_url( 'ecurring_return' );
	    $return_url = $this->removeTrailingSlashAfterParamater( $return_url );

	    if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		    $return_url = add_query_arg(array(
			    'order_id'       => $order->id,
			    'key'            => $order->order_key,
		    ), $return_url);
	    } else {
		    $return_url = add_query_arg(array(
			    'order_id'       => $order->get_id(),
			    'key'            => $order->get_order_key(),
		    ), $return_url);
	    }

        $lang_url   = $this->getSiteUrlWithLanguage();
        $return_url = str_replace($site_url, $lang_url, $return_url);

        return apply_filters(WOOECUR_PLUGIN_ID . '_return_url', $return_url, $order);
    }

    /**
     * @param WC_Order $order
     * @return string
     */
    protected function getWebhookUrl (WC_Order $order)
    {
        $site_url    = get_site_url();

	    $webhook_url = WC()->api_request_url( strtolower( get_class( $this ) ) );
	    $webhook_url = $this->removeTrailingSlashAfterParamater( $webhook_url );

	    if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		    $webhook_url = add_query_arg(array(
			    'order_id' => $order->id,
			    'key'      => $order->order_key,
		    ), $webhook_url);
	    } else {
		    $webhook_url = add_query_arg(array(
			    'order_id' => $order->get_id(),
			    'key'      => $order->get_order_key(),
		    ), $webhook_url);
	    }

        $lang_url    = $this->getSiteUrlWithLanguage();
        $webhook_url = str_replace($site_url, $lang_url, $webhook_url);

        // Some (multilanguage) plugins will add a extra slash to the url (/nl//) causing the URL to redirect and lose it's data.
	    // Status updates via webhook will therefor not be processed. The below regex will find and remove those double slashes.
	    $webhook_url = preg_replace('/([^:])(\/{2,})/', '$1/', $webhook_url);

	    if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		    eCurring_WC_Plugin::debug( $this->id . ': Order ' . $order->id . ' webhookUrl: ' . $webhook_url, true );
	    } else {
		    eCurring_WC_Plugin::debug( $this->id . ': Order ' . $order->get_id() . ' webhookUrl: ' . $webhook_url, true );
	    }

        return apply_filters(WOOECUR_PLUGIN_ID . '_webhook_url', $webhook_url, $order);
    }

	/**
	 * Remove a trailing slash after a query string if there is one in the WooCommerce API request URL.
	 * For example WMPL adds a query string with trailing slash like /?lang=de/ to WC()->api_request_url.
	 * This causes issues when we append to that URL with add_query_arg.
	 *
	 * @return string
	 */
	protected function removeTrailingSlashAfterParamater( $url ) {

		if ( strpos( $url, '?' ) ) {
			$url = eCurring_WC_Plugin::getDataHelper()->untrailingslashit( $url );
		}

		return $url;
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
     * @return string|NULL
     */
    protected function getSelectedIssuer ()
    {
        $issuer_id = WOOECUR_PLUGIN_ID . '_issuer_' . $this->id;

        return !empty($_POST[$issuer_id]) ? $_POST[$issuer_id] : NULL;
    }


	/**
	 */
	protected function getOrderCurrency( WC_Order $order ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			return $order->get_order_currency();
		} else {
			return $order->get_currency();
		}
	}

    /**
     * @return array
     */
    protected function getSupportedCurrencies ()
    {
	    $default = array (
		    'EUR'
	    );

        return apply_filters('woocommerce_' . $this->id . '_supported_currencies', $default);
    }

    /**
     * @return bool
     */
    protected function isCurrencySupported ()
    {
        return in_array(get_woocommerce_currency(), $this->getSupportedCurrencies());
    }

    /**
     * @return bool
     */
    protected function isValidApiKeyProvided ()
    {
        $settings  = eCurring_WC_Plugin::getSettingsHelper();
        $api_key   = $settings->getApiKey();

        return !empty($api_key) && preg_match('^\w{40,}$^', $api_key);
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

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$order_id = $order->id;
		} else {
			$order_id = $order->get_id();
		}

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
