<?php


namespace Ecurring\WooEcurring\AdminPages;


use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\Element\FormInterface;
use ChriCo\Fields\View\RenderableElementInterface;

/**
 * Service able to build settings form.
 */
interface FormBuilderInterface {

	/**
	 * Build a settings form instance from configuration.
	 *
	 * @return ElementInterface Form instance.
	 */
	public function buildForm(): ElementInterface;


	/**
	 * Build a renderable form view instance.
	 *
	 * @param FormInterface $form Form to create a view from.
	 *
	 * @return RenderableElementInterface Form view ready to be rendered.
	 */
	public function buildFormView(FormInterface $form): RenderableElementInterface;
}
