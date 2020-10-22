<?php
class eCurring_WC_Helper_Settings
{
    const DEFAULT_TIME_PAYMENT_CONFIRMATION_CHECK = '3:00';

    protected $ecurringSettings;

	public function __construct() {
		$this->ecurringSettings = get_option('ecurring_wc_gateway_ecurring_settings');
	}

    /**
     * @return null|string
     */
    public function getApiKey ()
    {
        return isset($this->ecurringSettings['api_key']) ? trim($this->ecurringSettings['api_key']) : '';
    }

    /**
     * Description send to eCurring
     *
     * @return string|null
     */
    public function getPaymentDescription ()
    {
        return trim(get_option($this->getSettingId('payment_description')));
    }

	/**
	 * Order status for cancelled payments
	 *
	 * @return string|null
	 */
	public function getOrderStatusCancelledPayments ()
	{
		return isset($this->ecurringSettings['order_status_cancelled_payments']) ?
			trim($this->ecurringSettings['order_status_cancelled_payments']) : 'pending';
	}

    /**
     * @return bool
     */
    public function isDebugEnabled ()
    {
		return isset($this->ecurringSettings['debug']) ? $this->ecurringSettings['debug'] === 'yes' : true;
    }

    /**
     * @return string
     */
    public function getGlobalSettingsUrl ()
    {
        return admin_url('admin.php?page=wc-settings&tab=checkout#' . WOOECUR_PLUGIN_ID);
    }

    /**
     * @return string
     */
    public function getLogsUrl ()
    {
        return admin_url('admin.php?page=wc-status&tab=logs');
    }

    /**
     * Get plugin status
     *
     * - Check compatibility
     * - Check eCurring API connectivity
     *
     * @return string
     */
    public function getPluginStatus ()
    {
        $status = eCurring_WC_Plugin::getStatusHelper();

        if (!$status->isCompatible())
        {
            // Just stop here!
            return ''
                . '<div class="notice notice-error">'
                . '<p><strong>' . __('Error', 'woo-ecurring') . ':</strong> ' . implode('<br/>', $status->getErrors())
                . '</p></div>';
        }

        try
        {
            // Check compatibility
            $status->geteCurringApiStatus();

            $api_status       = ''
                . '<p>' . __('eCurring status:', 'woo-ecurring')
                . ' <span style="color:green; font-weight:bold;">' . __('Connected', 'woo-ecurring') . '</span>'
                . '</p>';
            $api_status_type = 'updated';
        }
        catch (Exception $e)
        {

            $api_status = ''
                . '<p style="font-weight:bold;"><span style="color:red;">Communicating with eCurring failed:</span> ' . $e->getMessage() . '</p>';

            $api_status_type = 'error';
        }

        return ''
            . '<div id="message" class="' . $api_status_type . ' fade notice">'
            . $api_status
            . '</div>';
    }

    /**
     * @param string $gateway_class_name
     * @return string
     */
    protected function getGatewaySettingsUrl ($gateway_class_name)
    {
        return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . sanitize_title(strtolower($gateway_class_name)));
    }

    public function getPaymentConfirmationCheckTime()
    {
        $time = strtotime(self::DEFAULT_TIME_PAYMENT_CONFIRMATION_CHECK);
        $date = new DateTime();

        if ($date->getTimestamp() > $time){
            $date->setTimestamp($time);
            $date->add(new DateInterval('P1D'));
        } else {
            $date->setTimestamp($time);
        }


        return $date->getTimestamp();
    }

    /**
     * @param string $setting
     * @return string
     */
    protected function getSettingId ($setting)
    {
        global $wp_version;

        $setting_id        = WOOECUR_PLUGIN_ID . '_' . trim($setting);
        $setting_id_length = strlen($setting_id);

        $max_option_name_length = 191;

        /**
         * Prior to WooPress version 4.4.0, the maximum length for wp_options.option_name is 64 characters.
         * @see https://core.trac.wordpress.org/changeset/34030
         */
        if ($wp_version < '4.4.0') {
            $max_option_name_length = 64;
        }

        if ($setting_id_length > $max_option_name_length)
        {
            trigger_error("Setting id $setting_id ($setting_id_length) to long for database column wp_options.option_name which is varchar($max_option_name_length).", E_USER_WARNING);
        }

        return $setting_id;
    }

}
