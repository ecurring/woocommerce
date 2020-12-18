<?php

namespace Ecurring\WooEcurring\Template;

use Dhii\Output\Block\BlockInterface;
use Dhii\Output\Exception\CouldNotRenderExceptionInterface;
use Dhii\Output\Exception\RendererExceptionInterface;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception;
use Throwable;

/**
 * Template part that represents an HTML block containing select element.
 */
class SelectBlock implements BlockInterface
{
    /**
     * @var array
     */
    protected $context;

    /**
     * @param array $context
     */
    public function __construct(array $context)
    {
        $this->context = $context;
    }

    public function render(): string
    {
        return sprintf('<div>%1$s</div>', $this->context['wc_select']->render());
    }

    public function __toString(): string
    {
        try {
            $content = $this->render();
        } catch (Throwable $throwable) {
            trigger_error('Caught a throwable when trying to render a template: %1$s', $throwable->getMessage());
            $content = '';
        }

        return $content;
    }
}
