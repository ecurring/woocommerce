<?php

namespace Ecurring\WooEcurringTests\AdminPages;

use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\View\RenderableElementInterface;
use Dhii\Output\Template\TemplateInterface;
use Ecurring\WooEcurring\AdminPages\AdminController;
use Ecurring\WooEcurring\AdminPages\Form\FormFieldsCollectionBuilderInterface;
use Ecurring\WooEcurring\AdminPages\Form\NonceFieldBuilderInterface;
use Ecurring\WooEcurring\Settings\SettingsCrudInterface;
use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;

use function Brain\Monkey\Functions\when;

class AdminControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp()
    {

        $_POST = [];

        parent::setUp();
    }

    public function testRegisterPluginSettingsTab()
    {
        /** @var TemplateInterface&MockObject $adminSettingsPageRendererMock */
        $adminSettingsPageRendererMock = $this->createMock(TemplateInterface::class);

        /** @var FormFieldsCollectionBuilderInterface&MockObject $formBuilderMock */
        $formBuilderMock = $this->createMock(FormFieldsCollectionBuilderInterface::class);

        /** @var SettingsCrudInterface&MockObject $settingsCrudMock */
        $settingsCrudMock = $this->createMock(SettingsCrudInterface::class);

        /** @var NonceInterface&MockObject $nonceMock */
        $nonceMock = $this->createMock(NonceInterface::class);

        /** @var NonceFieldBuilderInterface&MockObject $nonceFieldBuilderMock */
        $nonceFieldBuilderMock = $this->createMock(NonceFieldBuilderInterface::class);

        $sut = new AdminController(
            $adminSettingsPageRendererMock,
            $formBuilderMock,
            $settingsCrudMock,
            '',
            $nonceMock,
            $nonceFieldBuilderMock
        );

        when('_x')->returnArg();

        $tabs = [];

        $tabsWithPluginTab = $sut->registerPluginSettingsTab($tabs);

        $this->assertArrayHasKey('mollie_subscriptions', $tabsWithPluginTab);
    }

    public function testRenderPluginSettingsPage()
    {
        /** @var TemplateInterface&MockObject $adminSettingsPageRendererMock */
        $adminSettingsPageRendererMock = $this->createMock(TemplateInterface::class);

        $renderedContent = 'some rendered content';

        $adminSettingsPageRendererMock->expects($this->once())
            ->method('render')
            ->willReturn($renderedContent);

        /** @var CollectionElementInterface&MockObject $formFieldsMock */
        $formFieldsMock = $this->createMock(CollectionElementInterface::class);

        /** @var RenderableElementInterface&MockObject $formViewMock */
        $formViewMock = $this->createMock(RenderableElementInterface::class);

        /** @var FormFieldsCollectionBuilderInterface&MockObject $formBuilderMock */
        $formBuilderMock = $this->createMock(FormFieldsCollectionBuilderInterface::class);

        $formBuilderMock->expects($this->once())
            ->method('buildFieldsCollection')
            ->willReturn($formFieldsMock);

        $formBuilderMock->expects($this->once())
            ->method('buildFormFieldsCollectionView')
            ->willReturn($formViewMock);

        /** @var SettingsCrudInterface&MockObject $settingsCrudMock */
        $settingsCrudMock = $this->createMock(SettingsCrudInterface::class);

        /** @var NonceInterface&MockObject $nonceMock */
        $nonceMock = $this->createMock(NonceInterface::class);

        /** @var NonceFieldBuilderInterface&MockObject $nonceFieldBuilderMock */
        $nonceFieldBuilderMock = $this->createMock(NonceFieldBuilderInterface::class);

        $sut = new AdminController(
            $adminSettingsPageRendererMock,
            $formBuilderMock,
            $settingsCrudMock,
            '',
            $nonceMock,
            $nonceFieldBuilderMock
        );

        $this->expectOutputString($renderedContent);

        $sut->renderPluginSettingsPage();
    }
}
