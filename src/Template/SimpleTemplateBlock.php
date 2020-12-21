<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Template;

use ArrayAccess;
use Dhii\Output\Block\BlockInterface;
use Dhii\Output\Template\TemplateInterface;
use Throwable;

/**
 * Simple template block that renders provided template wrapped into <div> container.
 */
class SimpleTemplateBlock implements BlockInterface
{
    /**
     * @var array
     */
    protected $context;
    /**
     * @var TemplateInterface
     */
    protected $template;

    /**
     * @param TemplateInterface $template Template to render with context.
     * @param array|ArrayAccess|null $context Context to provide to the template.
     */
    public function __construct(TemplateInterface $template, $context)
    {
        $this->context = $context;
        $this->template = $template;
    }

    public function render(): string
    {
        return sprintf('<div>%1$s</div>', $this->template->render($this->context));
    }

    public function __toString(): string
    {
        try {
            $content = $this->render();
        } catch (Throwable $throwable) {
            trigger_error(
                wp_kses_post(sprintf(
                    'Caught a throwable when trying to render a template: %1$s',
                    $throwable->getMessage()
                ))
            );
            $content = '';
        }

        return $content;
    }
}
