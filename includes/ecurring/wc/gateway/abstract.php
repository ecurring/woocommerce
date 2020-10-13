<?php

abstract class eCurring_WC_Gateway_Abstract extends WC_Payment_Gateway
{

    /**
     *
     */
    public function __construct ()
    {
        add_action('woocommerce_email_after_order_table', array($this, 'displayInstructions'), 10, 3);
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

	    $subscription = eCurring_WC_Plugin::getDataHelper()->getActiveSubscription($order->get_id());

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
	 * @return bool|string
	 */
	protected function getInitialOrderStatus (WC_Order $order) {
		$initial_order = $order->get_parent_id();
		return $initial_order != 0 ? wc_get_order($initial_order)->get_status() : false;
	}

}
