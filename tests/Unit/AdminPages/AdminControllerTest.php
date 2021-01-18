<?php

namespace Ecurring\WooEcurringTests\Unit\AdminPages;

use Brain\Nonces\NonceInterface;
use ChriCo\Fields\Element\CollectionElementInterface;
use ChriCo\Fields\View\RenderableElementInterface;
use Dhii\Output\Template\TemplateInterface;
use Ecurring\WooEcurring\AdminPages\AdminController;
use Ecurring\WooEcurring\AdminPages\Form\FormFieldsCollectionBuilderInterface;
use Ecurring\WooEcurring\AdminPages\Form\NonceFieldBuilderInterface;
use Ecurring\WooEcurring\AdminPages\OrderEditPageController;
use Ecurring\WooEcurring\AdminPages\ProductEditPageController;
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

        when('plugin_dir_path')->justReturn('');

        if (! defined('WOOECUR_PLUGIN_FILE')) {
            define('WOOECUR_PLUGIN_FILE', 'woo-ecurring/woo-ecurring.php');
        }

        $productEditPageControllerMock = $this->createMock(ProductEditPageController::class);

        $orderEditPageControllerMock = $this->createMock(OrderEditPageController::class);

        $sut = new AdminController(
            $adminSettingsPageRendererMock,
            $formBuilderMock,
            $settingsCrudMock,
            '',
            $nonceMock,
            $nonceFieldBuilderMock,
            $productEditPageControllerMock,
            $orderEditPageControllerMock
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

        when('plugin_dir_path')->justReturn('');

        if (! defined('WOOECUR_PLUGIN_FILE')) {
            define('WOOECUR_PLUGIN_FILE', 'woo-ecurring/woo-ecurring.php');
        }

        $productEditPageControllerMock = $this->createMock(ProductEditPageController::class);

        $orderEditPageControllerMock = $this->createMock(OrderEditPageController::class);

        $sut = new AdminController(
            $adminSettingsPageRendererMock,
            $formBuilderMock,
            $settingsCrudMock,
            '',
            $nonceMock,
            $nonceFieldBuilderMock,
            $productEditPageControllerMock,
            $orderEditPageControllerMock
        );

        $this->expectOutputString($renderedContent);

        $sut->renderPluginSettingsPage();
    }

    public function testHandleRegisteringMetaBox()
    {
        $orderEditPageControllerMock = $this->createMock(OrderEditPageController::class);
        $orderEditPageControllerMock->expects($this->once())
            ->method('registerEditOrderPageMetaBox');

        $sut = new AdminController(
            $this->createMock(TemplateInterface::class),
            $this->createMock(FormFieldsCollectionBuilderInterface::class),
            $this->createMock(SettingsCrudInterface::class),
            '',
            $this->createMock(NonceInterface::class),
            $this->createMock(NonceFieldBuilderInterface::class),
            $this->createMock(ProductEditPageController::class),
            $orderEditPageControllerMock
        );

        $sut->handleRegisteringMetaBoxes();
    }
}
