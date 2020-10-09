<?php


namespace Ecurring\WooEcurring\AdminPages;


use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\Element\FormInterface;
use ChriCo\Fields\Exception\MissingAttributeException;
use ChriCo\Fields\Exception\UnknownTypeException;
use ChriCo\Fields\View\RenderableElementInterface;

/**
 * Service able to build settings form.
 */
interface FormFieldsCollectionBuilderInterface {

	/**
	 * Build a settings form instance from configuration.
	 *
	 * @return ElementInterface Form fields collection instance.
	 *
	 * @throws UnknownTypeException
	 * @throws MissingAttributeException
	 */
	public function buildFieldsCollection(): ElementInterface;

	/**
	 * Build a renderable form view instance.
	 *
	 * @param FormInterface $form Form to create a view from.
	 *
	 * @return RenderableElementInterface Form view ready to be rendered.
	 *
	 * @throws UnknownTypeException
	 */
	public function buildFormFieldsCollectionView(CollectionElementInterface $form): RenderableElementInterface;
}
