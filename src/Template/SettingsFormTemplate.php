<?php

namespace Ecurring\WooEcurring\Template;

use ChriCo\Fields\Element\FormInterface;
use ChriCo\Fields\View\RenderableElementInterface;
use Dhii\Output\Template\TemplateInterface;

class SettingsFormTemplate implements TemplateInterface {

	/**
	 * @inheritDoc
	 */
	public function render($context = null) {

		/**
		 * @var FormInterface $form
		 */
		$form = $context['form'];

		/**
		 * @var RenderableElementInterface
		 */
		$view = $context['view'];

		return $view->render($form);
	}
}
