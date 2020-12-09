<?php

namespace Ecurring\WooEcurringTests\AdminPages\Form;


use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\ElementFactory;
use ChriCo\Fields\View\RenderableElementInterface;
use ChriCo\Fields\ViewFactory;
use Ecurring\WooEcurring\AdminPages\Form\FormFieldsCollectionBuilder;
use Ecurring\WooEcurringTests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class FormFieldsCollectionBuilderTest extends TestCase
{

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

        $sut = new FormFieldsCollectionBuilder(
            $elementFactoryMock,
            $viewFactoryMock,
            $formFields
        );

        $this->assertSame($elementMock, $sut->buildFieldsCollection());
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

        $sut = new FormFieldsCollectionBuilder(
            $elementFactoryMock,
            $viewFactoryMock,
            $formFields
        );

        $this->assertSame($collectionViewMock, $sut->buildFormFieldsCollectionView());
    }
}
