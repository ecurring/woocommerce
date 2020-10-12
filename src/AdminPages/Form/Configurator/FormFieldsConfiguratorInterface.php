<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages\Form\Configurator;

use ChriCo\Fields\Element\CollectionElementInterface;

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
	 */
	public function configure(CollectionElementInterface $fields): CollectionElementInterface;
}
