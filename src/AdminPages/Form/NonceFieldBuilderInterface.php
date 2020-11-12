<?php

namespace Ecurring\WooEcurring\AdminPages\Form;


use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\View\RenderableElementInterface;

/**
 * Service able to build nonce fields from given nonce.
 */
interface NonceFieldBuilderInterface
{

    /**
     * @param NonceInterface $nonce Nonce instance to take data from.
     *
     * @return ElementInterface Nonce field.
     */
    public function buildNonceField(NonceInterface $nonce): ElementInterface;

    /**
     * Renderable element.
     */
    public function buildNonceFieldView(): RenderableElementInterface;
}
