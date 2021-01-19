<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\PaymentGatewaysFilter;

use WC_Payment_Gateway;

interface PaymentGatewaysFilterInterface
{

    /**
     * Filter gateway list by some criteria.
     *
     * @param WC_Payment_Gateway[] $gateways Initial gateways list.
     *
     * @return WC_Payment_Gateway[] Filtered gateways list.
     */
    public function filter(array $gateways): array;
}
