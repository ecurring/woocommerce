<?php

abstract class eCurring_WC_Gateway_Abstract extends WC_Payment_Gateway
{
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
        add_action('woocommerce_email_after_order_table', array($this, 'displayInstructions'), 10, 3);

	    // Adjust title and text on Order Received page in some cases, see issue #166
	    add_filter( 'the_title', array ( $this, 'onOrderReceivedTitle' ), 10, 2 );}

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
	 * @return bool|string
	 */
	protected function getInitialOrderStatus (WC_Order $order) {
		$initial_order = $order->get_parent_id();
		return $initial_order != 0 ? wc_get_order($initial_order)->get_status() : false;
	}

}
