<?php


namespace Ecurring\WooEcurring\AdminPages;


use Brain\Nonces\NonceInterface;
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
	 * @var array
	 */
	protected $formFields;
	/**
	 * @var NonceInterface
	 */
	protected $nonce;

	/**
	 * @param ElementFactory $elementFactory
	 * @param ViewFactory    $viewFactory
	 * @param array          $formFields
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
	public function buildForm(): ElementInterface {
		return $this->elementFactory->create($this->formFields);
	}

	/**
	 * @inheritDoc
	 */
	public function buildFormView(FormInterface $form): RenderableElementInterface {
		return $this->viewFactory->create('form');
	}
}
