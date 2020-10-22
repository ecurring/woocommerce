<?php


namespace Ecurring\WooEcurringTests\AdminPages\Form;


use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\View\RenderableElementInterface;
use ChriCo\Fields\ViewFactory;
use Ecurring\WooEcurring\AdminPages\Form\NonceFieldBuilder;
use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;

class NonceFieldBuilderTest extends TestCase {

	use MockeryPHPUnitIntegration;

	public function testBuildNonceField()
	{
		/** @var ElementFactory&MockObject $elementFactoryMock */
		$elementFactoryMock = $this->createMock(ElementFactory::class);

		$nonceAction = 'nonceaction';
		$nonceValue = 'nonce123';

		/** @var NonceInterface&MockObject $nonceMock */
		$nonceMock = $this->createConfiguredMock(
			NonceInterface::class,
			[
				'__toString' => $nonceValue,
				'action' => $nonceAction
			]
		);

		$elementMock = $this->createMock(ElementInterface::class);

		$elementFactoryMock->expects( $this->once() )
           ->method( 'create' )
           ->with( [
	                   'attributes' =>
		                   [
			                   'name'  => $nonceAction,
			                   'type'  => 'hidden',
			                   'value' => $nonceValue
		                   ],
	               ]
	           )
           ->willReturn( $elementMock );

		/** @var ViewFactory&MockObject $viewFactoryMock */
		$viewFactoryMock = $this->createMock(ViewFactory::class);

		$sut = new NonceFieldBuilder($elementFactoryMock, $viewFactoryMock);

		$field = $sut->buildNonceField($nonceMock);

		$this->assertInstanceOf(ElementInterface::class, $field);

	}


	public function testBuildNonceFieldView()
	{
		/** @var ElementInterface&MockObject $elementViewMock */
		$elementViewMock = $this->createMock(RenderableElementInterface::class);

		/** @var ViewFactory&MockObject $viewFactoryMock */
		$viewFactoryMock = $this->createMock(ViewFactory::class);

		$viewFactoryMock->expects($this->once())
			->method('create')
			->with('hidden')
			->willReturn($elementViewMock);

		/** @var ElementFactory&MockObject $elementFactoryMock */
		$elementFactoryMock = $this->createMock(ElementFactory::class);

		$sut = new NonceFieldBuilder($elementFactoryMock, $viewFactoryMock);
		$fieldView = $sut->buildNonceFieldView();

		$this->assertInstanceOf(RenderableElementInterface::class, $fieldView);
	}
}
