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
     * WcSelect constructor.
     *
     * @param array $context
     *    @type array $args Arguments for rendering
     *                      Woocommerce Select {@link woocommerce_wp_select()}
     *
     */
    public function __construct(array $context = [])
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function render($context = null): string
    {
        if (! $this->context['args'] || ! is_array($this->context['args'])) {
            throw new Exception(
                'Not found expected `args` element in the context or it is not an array.'
            );
        }
        ob_start();

        if (! function_exists('woocommerce_wp_select')) {
            throw new Exception(
                'Not found function `woocommerce_wp_select` ' .
                'but it is required for rendering template part.'
            );
        }

        woocommerce_wp_select($this->context['args']);

        return ob_get_clean();
    }
}
