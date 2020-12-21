<?php

namespace Ecurring\WooEcurringTests\Unit\AdminPages;

use Dhii\Output\Block\BlockInterface;
use Dhii\Output\Block\TemplateBlockFactoryInterface;
use Dhii\Output\Template\PathTemplateFactoryInterface;
use Dhii\Output\Template\TemplateInterface;
use Ecurring\WooEcurring\AdminPages\ProductEditPageController;
use Ecurring\WooEcurring\Api\SubscriptionPlans;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use Ecurring\WooEcurringTests\TestCase;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;

use WC_Product;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class ProductEditPageControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider productDataFieldsTabsProvider
     */
    public function testAddProductDataTabToTheDataTabsList(array $existingTabs)
    {
        /** @var PathTemplateFactoryInterface&MockObject $pathTemplateFactoryMock */
        $pathTemplateFactoryMock = $this->createMock(PathTemplateFactoryInterface::class);

        /**
         * @var TemplateBlockFactoryInterface&MockObject
         */
        $templateBlockFactoryMock = $this->createMock(TemplateBlockFactoryInterface::class);

        /** @var SubscriptionPlans&MockObject $subscriptionPlansMock */
        $subscriptionPlansMock = $this->createMock(SubscriptionPlans::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        $sut = new ProductEditPageController(
            $pathTemplateFactoryMock,
            $templateBlockFactoryMock,
            $subscriptionPlansMock,
            $subscriptionCrudMock,
            ''
        );

        when('_x')
            ->returnArg(1);

        $newTabs = $sut->addProductDataTabsToTheDataTabsList($existingTabs);

        $this->assertArrayHasKey('woo-ecurring-tab', $newTabs);
        $this->assertArrayHasKey('target', $newTabs['woo-ecurring-tab']);
        $this->assertArrayHasKey('label', $newTabs['woo-ecurring-tab']);
    }

    public function productDataFieldsTabsProvider(): array
    {
        $emptyTabsSet = [];
        $twoTabsSet = [
            'tab_one' => [
                'label' => '',
                'target' => '',
            ],
            'tab_two' => [
                'label' => 'label',
                'target' => 'target',
            ],
        ];

        return [
            [
                $emptyTabsSet,
            ],
            [
                $twoTabsSet,
            ],
        ];
    }

    public function testSavePostedProductFieldsSubscriptionNotEmpty()
    {
        $subscriptionId = 'testsubscriptionid123';
        $productId = 10;

        when('filter_input')
            ->justReturn($subscriptionId);

        /** @var PathTemplateFactoryInterface&MockObject $pathTemplateFactoryMock */
        $pathTemplateFactoryMock = $this->createMock(PathTemplateFactoryInterface::class);

        /**
         * @var TemplateBlockFactoryInterface&MockObject
         */
        $templateBlockFactoryMock = $this->createMock(TemplateBlockFactoryInterface::class);

        /** @var SubscriptionPlans&MockObject $subscriptionPlansMock */
        $subscriptionPlansMock = $this->createMock(SubscriptionPlans::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        $sut = new ProductEditPageController(
            $pathTemplateFactoryMock,
            $templateBlockFactoryMock,
            $subscriptionPlansMock,
            $subscriptionCrudMock,
            ''
        );

        expect('update_post_meta')
            ->once()
            ->with($productId, '_ecurring_subscription_plan', $subscriptionId);

        $sut->savePostedProductFields(10);
    }

    public function testSavePostedProductFieldsSubscriptionEmpty()
    {
        $productId = 10;

        when('filter_input')
            ->justReturn(false);

        /** @var PathTemplateFactoryInterface&MockObject $pathTemplateFactoryMock */
        $pathTemplateFactoryMock = $this->createMock(PathTemplateFactoryInterface::class);

        /**
         * @var TemplateBlockFactoryInterface&MockObject
         */
        $templateBlockFactoryMock = $this->createMock(TemplateBlockFactoryInterface::class);

        /** @var SubscriptionPlans&MockObject $subscriptionPlansMock */
        $subscriptionPlansMock = $this->createMock(SubscriptionPlans::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        $sut = new ProductEditPageController(
            $pathTemplateFactoryMock,
            $templateBlockFactoryMock,
            $subscriptionPlansMock,
            $subscriptionCrudMock,
            ''
        );

        expect('delete_post_meta')
            ->once()
            ->with($productId, '_ecurring_subscription_plan');

        $sut->savePostedProductFields(10);
    }

    public function testRenderProductDataFields()
    {
        $templateContent = 'This is the template content.';
        $productId = 12;
        $productMock = $this->createMock(WC_Product::class);
        $productSubscriptionId = '123456';

        /** @var TemplateBlockFactoryInterface&MockObject $selectBlockMock */
        $selectBlockMock = $this->createMock(BlockInterface::class);

        /** @var TemplateInterface&MockObject $tabContentTemplateMock */
        $tabContentTemplateMock = $this->createMock(TemplateInterface::class);

        $tabContentTemplateMock->expects($this->once())
            ->method('render')
            ->with(['select' => $selectBlockMock])
            ->willReturnCallback(function() use ($templateContent){
                echo $templateContent;
            });

        /** @var PathTemplateFactoryInterface&MockObject $pathTemplateFactoryMock */
        $pathTemplateFactoryMock = $this->createMock(PathTemplateFactoryInterface::class);
        $pathTemplateFactoryMock->method('fromPath')
            ->willReturn($tabContentTemplateMock);

        /**
         * @var TemplateBlockFactoryInterface&MockObject
         */
        $templateBlockFactoryMock = $this->createMock(TemplateBlockFactoryInterface::class);
        $templateBlockFactoryMock->method('fromTemplate')
            ->willReturn($selectBlockMock);

        /** @var SubscriptionPlans&MockObject $subscriptionPlansMock */
        $subscriptionPlansMock = $this->createMock(SubscriptionPlans::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);
        $subscriptionCrudMock->method('getProductSubscriptionId')
            ->with($productMock)
            ->willReturn($productSubscriptionId);

        $sut = new ProductEditPageController(
            $pathTemplateFactoryMock,
            $templateBlockFactoryMock,
            $subscriptionPlansMock,
            $subscriptionCrudMock,
            ''
        );

        when('wc_get_product')
            ->justReturn($productMock);

        expect('wp_kses')
            ->andReturnFirstArg();
        when('__')
            ->returnArg(1);
        when('_x')
            ->returnArg(1);

        $this->expectOutputString($templateContent);

        $sut->renderProductDataFields($productId);
    }

    public function testRenderProductDataFieldsExceptionLogged()
    {
        $pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $pluginMock->shouldReceive('debug');

        $productId = 15;
        $productMock = $this->createMock(WC_Product::class);
        $productSubscriptionId = '123456';

        /** @var TemplateBlockFactoryInterface&MockObject $selectBlockMock */
        $selectBlockMock = $this->createMock(BlockInterface::class);

        /** @var TemplateInterface&MockObject $tabContentTemplateMock */
        $tabContentTemplateMock = $this->createMock(TemplateInterface::class);

        $exception = new Exception();

        $tabContentTemplateMock->expects($this->once())
            ->method('render')
            ->with(['select' => $selectBlockMock])
            ->willThrowException($exception);

        /** @var PathTemplateFactoryInterface&MockObject $pathTemplateFactoryMock */
        $pathTemplateFactoryMock = $this->createMock(PathTemplateFactoryInterface::class);
        $pathTemplateFactoryMock->method('fromPath')
            ->willReturn($tabContentTemplateMock);

        /**
         * @var TemplateBlockFactoryInterface&MockObject
         */
        $templateBlockFactoryMock = $this->createMock(TemplateBlockFactoryInterface::class);
        $templateBlockFactoryMock->method('fromTemplate')
            ->willReturn($selectBlockMock);

        /** @var SubscriptionPlans&MockObject $subscriptionPlansMock */
        $subscriptionPlansMock = $this->createMock(SubscriptionPlans::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);
        $subscriptionCrudMock->method('getProductSubscriptionId')
            ->with($productMock)
            ->willReturn($productSubscriptionId);

        $sut = new ProductEditPageController(
            $pathTemplateFactoryMock,
            $templateBlockFactoryMock,
            $subscriptionPlansMock,
            $subscriptionCrudMock,
            ''
        );

        when('wc_get_product')
            ->justReturn($productMock);

        when('__')
            ->returnArg(1);
        when('_x')
            ->returnArg(1);

        $this->expectOutputString('');

        $sut->renderProductDataFields($productId);
    }
}
