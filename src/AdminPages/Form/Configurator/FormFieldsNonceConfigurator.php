<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages\Form\Configurator;

use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\Exception\InvalidClassException;
use ChriCo\Fields\Exception\MissingAttributeException;
use ChriCo\Fields\Exception\UnknownTypeException;

/**
 * Service able to add nonce to the fields collection.
 */
class FormFieldsNonceConfigurator implements FormFieldsConfiguratorInterface {
	/**
	 * @var NonceInterface
	 */
	protected $nonce;
	/**
	 * @var ElementFactory
	 */
	protected $elementFactory;

	/**
	 * @param NonceInterface $nonce Nonce to add to the fields collection.
	 * @param ElementFactory $elementFactory
	 */
	public function __construct(NonceInterface $nonce, ElementFactory $elementFactory)
	{
		$this->nonce = $nonce;
		$this->elementFactory = $elementFactory;
	}


	/**
	 * @inheritDoc
	 */
	public function configure( CollectionElementInterface $fields ): CollectionElementInterface {
		$nonceField = $this->buildNonceField($this->nonce);

		return $fields->withElement($nonceField);
	}

	/**
	 * Create a nonce field interface from nonce instance.
	 *
	 * @param NonceInterface $nonce Nonce to take data from.
	 *
	 * @return ElementInterface Nonce field element.
	 *
	 * @throws InvalidClassException
	 * @throws UnknownTypeException|MissingAttributeException
	 */
	protected function buildNonceField( NonceInterface $nonce ): ElementInterface {
		return $this->elementFactory->create( [
			[
				'attributes' =>
					[
						'name'  => $nonce->action(),
						'type'  => 'hidden',
						'value' => (string) $nonce,
					],
			]
		] );
	}
}
