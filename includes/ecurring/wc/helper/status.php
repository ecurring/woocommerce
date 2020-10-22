<?php
class eCurring_WC_Helper_Status
{
    /**
     * Minimal required WooCommerce version
     *
     * @var string
     */
    const MIN_WOOCOMMERCE_VERSION = '4.0.0';

    /**
     * @var string[]
     */
    protected $errors = array();

    /**
     * @return bool
     */
    public function hasErrors ()
    {
        return !empty($this->errors);
    }

    /**
     * @return string[]
     */
    public function getErrors ()
    {
        return $this->errors;
    }

    /**
     * Check if this plugin is compatible
     *
     * @return bool
     */
    public function isCompatible ()
    {
        static $is_compatible = null;

        if ($is_compatible !== null)
        {
            return $is_compatible;
        }

        // Default
        $is_compatible = true;

        if (!$this->hasCompatibleWooCommerceVersion())
        {
            $this->errors[] = sprintf(
                /* translators: Placeholder 1: Plugin name, placeholder 2: required WooCommerce version, placeholder 3: used WooCommerce version */
                __('The %s plugin requires at least WooCommerce version %s, you are using version %s. Please update your WooCommerce plugin.', 'woo-ecurring'),
                WOOECUR_PLUGIN_TITLE,
                self::MIN_WOOCOMMERCE_VERSION,
                $this->getWooCommerceVersion()
            );

            return $is_compatible = false;
        }

        return $is_compatible;
    }

    /**
     * @return string
     */
    public function getWooCommerceVersion ()
    {
        return WooCommerce::instance()->version;
    }

    /**
     * @return bool
     */
    public function hasCompatibleWooCommerceVersion ()
    {
        return (bool) version_compare($this->getWooCommerceVersion(), self::MIN_WOOCOMMERCE_VERSION, ">=");
    }


    /**
     * @throws Exception
     */
    public function geteCurringApiStatus ()
    {
        try
        {
            $api_helper = eCurring_WC_Plugin::getApiHelper();
        }
        catch ( Exception $e )
        {

	        if ( $e->getMessage() == 'Error executing API call (401: Unauthorized Request): Missing authentication, or failed to authenticate.') {
		        throw new Exception(
			        'incorrect API key or other authentication issue. Please check your API key!'
		        );
	        }

            throw new Exception(
                $e->getMessage()
            );
        }
    }

}
