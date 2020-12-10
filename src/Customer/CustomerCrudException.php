<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use Ecurring\WooEcurring\EcurringException;

/**
 * Should be thrown if problems occurred when working with CustomerCrudInterface.
 */
class CustomerCrudException extends EcurringException
{
}
