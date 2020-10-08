<?php


namespace Ecurring\WooEcurring\AdminPages;


use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\Element\FormInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\View\RenderableElementInterface;
use ChriCo\Fields\ViewFactory;

class FormBuilder implements FormBuilderInterface {

	/**
	 * @var ViewFactory
	 */
	protected $viewFactory;
	/**
	 * @var array
	 */
	protected $settingsFormConfiguration;
	/**
	 * @var ElementFactory
	 */
	protected $elementFactory;

	/**
	 * @param ElementFactory $elementFactory
	 * @param ViewFactory    $viewFactory
	 * @param array          $settingsFormConfiguration
	 */
	public function __construct(
		ElementFactory $elementFactory,
		ViewFactory $viewFactory,
		array $settingsFormConfiguration
	) {
		$this->elementFactory = $elementFactory;
		$this->viewFactory = $viewFactory;
		$this->settingsFormConfiguration = $settingsFormConfiguration;
	}

	/**
	 * @inheritDoc
	 */
	public function buildForm(): ElementInterface {
		return $this->elementFactory->create($this->settingsFormConfiguration);
	}

	/**
	 * @inheritDoc
	 */
	public function buildFormView(FormInterface $form): RenderableElementInterface {
		return $this->viewFactory->create('form');
	}
}
