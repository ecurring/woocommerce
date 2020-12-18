<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Template;

use Dhii\Output\Block\BlockInterface;
use Dhii\Output\Template\TemplateInterface;
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
     * @var TemplateInterface
     */
    protected $wcSelectTemplate;

    /**
     * @param array             $context
     * @param TemplateInterface $wcSelectTemplate
     */
    public function __construct(array $context, TemplateInterface $wcSelectTemplate)
    {
        $this->context = $context;
        $this->wcSelectTemplate = $wcSelectTemplate;
    }

    public function render(): string
    {
        return sprintf('<div>%1$s</div>', $this->wcSelectTemplate->render($this->context));
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
