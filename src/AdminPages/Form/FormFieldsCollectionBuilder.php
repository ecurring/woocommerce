<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages\Form;

use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\View\RenderableElementInterface;
use ChriCo\Fields\ViewFactory;

class FormFieldsCollectionBuilder implements FormFieldsCollectionBuilderInterface
{

    /**
     * @var ViewFactory
     */
    protected $viewFactory;
    /**
     * @var ElementFactory
     */
    protected $elementFactory;
    /**
     * @var array
     */
    protected $formFields;

    /**
     * @param ElementFactory $elementFactory Service to build elements.
     * @param ViewFactory    $viewFactory Factory to build element view representation.
     * @param array          $formFields Fields configuration.
     */
    public function __construct(
        ElementFactory $elementFactory,
        ViewFactory $viewFactory,
        array $formFields
    ) {
        $this->elementFactory = $elementFactory;
        $this->viewFactory = $viewFactory;
        $this->formFields = $formFields;
    }

    /**
     * @inheritDoc
     */
    public function buildFieldsCollection(): CollectionElementInterface
    {

        return $this->elementFactory->create($this->formFields);
    }

    /**
     * @inheritDoc
     */
    public function buildFormFieldsCollectionView(): RenderableElementInterface
    {

        return $this->viewFactory->create('collection');
    }
}
