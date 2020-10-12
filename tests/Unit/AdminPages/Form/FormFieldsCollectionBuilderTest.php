<?php


namespace eCurring\WooEcurringTests\AdminPages\Form;


use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\View\RenderableElementInterface;
use ChriCo\Fields\ViewFactory;
use Ecurring\WooEcurring\AdminPages\Form\Configurator\FormFieldsConfiguratorInterface;
use Ecurring\WooEcurring\AdminPages\Form\FormFieldsCollectionBuilder;
use eCurring\WooEcurringTests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class FormFieldsCollectionBuilderTest extends TestCase {

	public function testBuildFieldsCollection()
	{
		/** @var CollectionElementInterface&MockObject $elementMock */
		$elementMock = $this->createMock(CollectionElementInterface::class);

		/** @var ElementFactory&MockObject $elementFactoryMock */
		$elementFactoryMock = $this->createMock(ElementFactory::class);

		$elementFactoryMock->expects($this->once())
			->method('create')
			->willReturn($elementMock);

		/** @var ViewFactory&MockObject $viewFactoryMock */
		$viewFactoryMock = $this->createMock(ViewFactory::class);

		$formFields = [];

		/** @var CollectionElementInterface&MockObject $configuredElementMock */
		$configuredElementMock = $this->createMock(CollectionElementInterface::class);

		/** @var CollectionElementInterface&MockObject $fullyConfiguredElementMock */
		$fullyConfiguredElementMock = $this->createMock(CollectionElementInterface::class);

		/** @var FormFieldsConfiguratorInterface&MockObject $configurator1 */
		$configurator1 = $this->createMock(FormFieldsConfiguratorInterface::class);

		/** @var FormFieldsConfiguratorInterface&MockObject $configurator2 */
		$configurator2 = $this->createMock(FormFieldsConfiguratorInterface::class);

		$configurator1->expects($this->once())
			->method('configure')
			->with($elementMock)
			->willReturn($configuredElementMock);

		$configurator2->expects($this->once())
			->method('configure')
			->with($configuredElementMock)
			->willReturn($fullyConfiguredElementMock);

		$formConfigurators = [
			$configurator1,
			$configurator2
		];

		$sut = new FormFieldsCollectionBuilder(
			$elementFactoryMock,
			$viewFactoryMock,
			$formFields,
			$formConfigurators
		);

		$this->assertSame($fullyConfiguredElementMock, $sut->buildFieldsCollection());
	}

	public function testBuildFormFieldsCollectionView()
	{
		/** @var ElementFactory&MockObject $elementFactoryMock */
		$elementFactoryMock = $this->createMock(ElementFactory::class);

		/** @var ViewFactory&MockObject $viewFactoryMock */
		$viewFactoryMock = $this->createMock(ViewFactory::class);

		$collectionViewMock = $this->createMock(RenderableElementInterface::class);

		$viewFactoryMock->expects($this->once())
			->method('create')
			->with('collection')
			->willReturn($collectionViewMock);

		$formFields = [];

		$formConfigurators = [];

		$sut = new FormFieldsCollectionBuilder(
			$elementFactoryMock,
			$viewFactoryMock,
			$formFields,
			$formConfigurators
		);

		$this->assertSame($collectionViewMock, $sut->buildFormFieldsCollectionView());
	}
}
