<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Template;

use Dhii\Output\Block\BlockInterface;
use Dhii\Output\Block\TemplateBlockFactoryInterface;
use Dhii\Output\Template\TemplateInterface;

/**
 * Service able to create SimpleTemplateBlock instance.
 */
class SimpleTemplateBlockFactory implements TemplateBlockFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function fromTemplate(TemplateInterface $template, $context): BlockInterface
    {
        return new SimpleTemplateBlock($context, $template);
    }
}
