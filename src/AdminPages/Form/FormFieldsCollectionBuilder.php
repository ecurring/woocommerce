<?php
declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages\Form;


use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\Exception\InvalidClassException;
use ChriCo\Fields\Exception\MissingAttributeException;
use ChriCo\Fields\Exception\UnknownTypeException;
use ChriCo\Fields\View\RenderableElementInterface;
use ChriCo\Fields\ViewFactory;
use Ecurring\WooEcurring\AdminPages\Form\Configurator\FormFieldsConfiguratorInterface;

class FormFieldsCollectionBuilder implements FormFieldsCollectionBuilderInterface {

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
	 * @var iterable<FormFieldsConfiguratorInterface>
	 */
	protected $formConfigurators;

	/**
	 * @param ElementFactory $elementFactory Service to build elements.
	 * @param ViewFactory    $viewFactory Factory to build element view representation.
	 * @param array          $formFields Fields configuration.
	 * @param iterable<FormFieldsConfiguratorInterface>      $formConfigurators Set of services able to modify built
	 *                                                                          fields collection.
	 */
	public function __construct(
		ElementFactory $elementFactory,
		ViewFactory $viewFactory,
		array $formFields,
		iterable $formConfigurators
	) {
		$this->elementFactory = $elementFactory;
		$this->viewFactory = $viewFactory;
		$this->formFields = $formFields;
		$this->formConfigurators = $formConfigurators;
	}

	/**
	 * @inheritDoc
	 */
	public function buildFieldsCollection(): CollectionElementInterface {
		$fieldsCollection = $this->elementFactory->create($this->formFields);

		return $this->applyConfigurators($fieldsCollection);
	}

	/**
	 * Apply form fields configurators to built fields collection.
	 *
	 * @param CollectionElementInterface $fieldsCollection Collection to apply configurators to.
	 *
	 * @return CollectionElementInterface
	 *
	 * @throws InvalidClassException
	 * @throws UnknownTypeException|MissingAttributeException
	 */
	protected function applyConfigurators(CollectionElementInterface $fieldsCollection): CollectionElementInterface
	{
		/** @var FormFieldsConfiguratorInterface $configurator */
		foreach($this->formConfigurators as $configurator)
		{
			$fieldsCollection = $configurator->configure($fieldsCollection);
		}

		return $fieldsCollection;
	}

	/**
	 * @inheritDoc
	 */
	public function buildFormFieldsCollectionView(CollectionElementInterface $form): RenderableElementInterface {
		return $this->viewFactory->create('collection');
	}
}
