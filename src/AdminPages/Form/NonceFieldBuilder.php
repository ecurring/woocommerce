<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages\Form;

use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\View\RenderableElementInterface;
use ChriCo\Fields\ViewFactory;

class NonceFieldBuilder implements NonceFieldBuilderInterface
{

    /**
     * @var ElementFactory
     */
    protected $elementFactory;
    /**
     * @var ViewFactory
     */
    protected $viewFactory;

    /**
     * @param ElementFactory $elementFactory
     * @param ViewFactory    $viewFactory
     */
    public function __construct(ElementFactory $elementFactory, ViewFactory $viewFactory)
    {

        $this->elementFactory = $elementFactory;
        $this->viewFactory = $viewFactory;
    }

    /**
     * @inheritDoc
     */
    public function buildNonceField(NonceInterface $nonce): ElementInterface
    {

        return $this->elementFactory->create([
            'attributes' =>
                [
                    'name' => $nonce->action(),
                    'type' => 'hidden',
                    'value' => (string) $nonce,
                ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function buildNonceFieldView(): RenderableElementInterface
    {

        return $this->viewFactory->create('hidden');
    }
}
