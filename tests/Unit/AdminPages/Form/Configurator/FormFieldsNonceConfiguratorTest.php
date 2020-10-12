<?php

namespace eCurring\WooEcurringTests\AdminPages\Form\Configurator;

use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\ElementFactory;
use Ecurring\WooEcurring\AdminPages\Form\Configurator\FormFieldsNonceConfigurator;
use eCurring\WooEcurringTests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class FormFieldsNonceConfiguratorTest extends TestCase {
	public function testConfigure()
	{
		$nonceValue = 'abc123';
		$nonceAction = 'nonceaction';

		/** @var NonceInterface&MockObject $nonceMock */
		$nonceMock = $this->createConfiguredMock(
			NonceInterface::class,
			[
				'action' => $nonceAction,
				'__toString' => $nonceValue
			]
		);

		/** @var ElementInterface&MockObject $nonceElementMock */
		$nonceElementMock = $this->createMock(ElementInterface::class);

		/** @var CollectionElementInterface&MockObject $configuredElementMock */
		$configuredElementMock = $this->createMock(CollectionElementInterface::class);

		/** @var CollectionElementInterface&MockObject $elementMock */
		$elementMock = $this->createMock(CollectionElementInterface::class);
		$elementMock->expects($this->once())
			->method('withElement')
			->with($nonceElementMock)
			->willReturn($configuredElementMock);

		/** @var ElementFactory&MockObject $elementFactoryMock */
		$elementFactoryMock = $this->createMock(ElementFactory::class);

		$elementFactoryMock->expects($this->once())
			->method('create')
			->with(
				[
					'attributes' =>
						[
							'name'  => $nonceAction,
							'type'  => 'hidden',
							'value' => $nonceValue,
						],
				]
			)
			->willReturn($nonceElementMock);

		$sut = new FormFieldsNonceConfigurator($nonceMock, $elementFactoryMock);

		$this->assertSame($configuredElementMock, $sut->configure($elementMock));
	}
}
