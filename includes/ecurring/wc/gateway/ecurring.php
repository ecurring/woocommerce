<?php

class eCurring_WC_Gateway_eCurring extends eCurring_WC_Gateway_Abstract
{
    /**
     *
     */
    public function __construct ()
    {
        $this->supports = array(
            'products',
        );

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getMethodId ()
    {
        return 'eCurring';
    }

    /**
     * @return string
     */
    public function getDefaultTitle ()
    {
        return __('eCurring', 'woo-ecurring');
    }

	/**
	 * @return string
	 */
	protected function getSettingsDescription() {
		return '';
	}

	/**
     * @return string
     */
    protected function getDefaultDescription ()
    {
        return '';
    }

	/**
	 * @param WC_Order $order
	 * @param          $subscription
	 * @param bool     $admin_instructions
	 * @param bool     $plain_text
	 *
	 * @return string|null
	 */
    protected function getInstructions (WC_Order $order, $subscription, $admin_instructions, $plain_text)
    {

		$subscription = eCurring_WC_Plugin::eCurringSubscription($subscription);

	    if ($subscription->active() && $subscription->cardHolder())
        {
            return sprintf(
                /* translators: Placeholder 1: card holder */
                __('Payment completed by <strong>%s</strong>', 'woo-ecurring'),
				$subscription->cardHolder()
            );
        }

	    return parent::getInstructions($order, $subscription, $admin_instructions, $plain_text);

    }
}
