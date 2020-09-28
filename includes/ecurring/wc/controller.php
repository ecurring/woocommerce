<?php

use Mollie\Api\Resources\Payment;

/**
 * Listen to application events and call relative handlers.
 */
class eCurring_WC_Controller
{
	public function __construct() {
		add_action(Mollie_WC_Plugin::PLUGIN_ID . '_payment_created', [$this, ]);
	}

	public function createEcurringSubscription( Payment $payment, WC_Order $order)
	{
		//todo: call create subscription here
	}
}
