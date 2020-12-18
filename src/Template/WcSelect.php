<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Template;

use Dhii\Output\Template\TemplateInterface;
use Exception;

/**
 * Template representing a Woocommerce-style select HTML element.
 */
class WcSelect implements TemplateInterface
{

    /**
     * @var array
     */
    protected $context;

    /**
     * @inheritDoc
     */
    public function render($context = null): string
    {
        ob_start();

        if (! function_exists('woocommerce_wp_select')) {
            throw new Exception(
                'Not found function `woocommerce_wp_select` ' .
                'but it is required for rendering template part.'
            );
        }

        woocommerce_wp_select($context ?? []);

        return ob_get_clean();
    }
}
