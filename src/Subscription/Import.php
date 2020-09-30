<?php

namespace eCurring\WooEcurring\Subscription;

use eCurring_WC_Helper_Api;

class Import
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    public function __construct(eCurring_WC_Helper_Api $api)
    {
        $this->api = $api;
    }

    public function import()
    {
        return '';
    }
}
