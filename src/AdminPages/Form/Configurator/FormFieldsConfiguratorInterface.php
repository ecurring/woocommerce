<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages\Form\Configurator;

use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\Exception\InvalidClassException;
use ChriCo\Fields\Exception\MissingAttributeException;
use ChriCo\Fields\Exception\UnknownTypeException;

/**
 * Service able to modify given fields collection.
 *
 * @return CollectionElementInterface Fields collection with modifications, may be not the same instance.
 */
interface FormFieldsConfiguratorInterface {

	/**
	 * @param CollectionElementInterface $fields Fields to modify.
	 *
	 * @return CollectionElementInterface Modified fields, may be another instance.
	 *
	 * @throws InvalidClassException
	 * @throws UnknownTypeException|MissingAttributeException
	 */
	public function configure(CollectionElementInterface $fields): CollectionElementInterface;
}
