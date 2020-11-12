<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Template;

use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\Element\FormInterface;
use ChriCo\Fields\View\RenderableElementInterface;
use Dhii\Output\Template\TemplateInterface;

class SettingsFormTemplate implements TemplateInterface
{

    /**
     * @inheritDoc
     */
    public function render($context = null): string
    {

        /**
         * @var FormInterface $form
         */
        $form = $context['form'];

        /**
         * @var RenderableElementInterface
         */
        $view = $context['view'];

        $mainFormFields = $view->render($form);

        /** @var ElementInterface $nonceField */
        $nonceField = $context['nonceField'];

        /** @var RenderableElementInterface $nonceView */
        $nonceView = $context['nonceFieldView'];

        $nonceFormFields = $nonceView->render($nonceField);

        return $mainFormFields . $nonceFormFields;
    }
}
