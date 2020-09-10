<?php


namespace eCurring\WooEcurringTests\Unit;


use eCurring\WooEcurringTests\TestCase;
use eCurring_WC_Exception_ApiClientException;
use eCurring_WC_Gateway_Abstract;
use eCurring_WC_Helper_Api;
use eCurring_WC_Helper_Data;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use WC_Order;
use WC_Order_Item_Product;
use function Brain\Monkey\Functions\expect;

class Gateway_AbstractTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testProcess_payment()
    {
        $orderId = 123;
        $ecurring_customer_id = '456';
        $ecurring_subscription_plan_id = '789';
        $product_id = 100;
        $order_key = 'abc';
        $subscription_data = [];
        $wcOrderMock = Mockery::mock(WC_Order::class);
        $wcOrderMock->shouldReceive('get_order_key')
            ->andReturn($order_key);
        $wcOrderMock->shouldReceive('get_id')
            ->andReturn($orderId);
        $wcOrderItemMock = Mockery::mock(WC_Order_Item_Product::class);
        $wcOrderItemMock->shouldReceive('get_product_id')
            ->andReturn($product_id);
        $wcOrderMock->shouldReceive('get_items')
            ->andReturn([$wcOrderItemMock]);
        $ecurringPluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);
        $dataHelperMock->expects($this->once())
            ->method('getWcOrder')
            ->with($orderId)
            ->willReturn($wcOrderMock);
        $dataHelperMock->expects($this->once())
            ->method('getUsereCurringCustomerId')
            ->willReturn($ecurring_customer_id);
        $ecurringPluginMock->shouldReceive('getDataHelper')
            ->andReturn($dataHelperMock);
        $ecurringPluginMock->shouldReceive('debug');
        $ecurringPluginMock->shouldReceive('addNotice');
        $apiHelperMock = Mockery::mock(eCurring_WC_Helper_Api::class);
        $apiHelperMock->shouldReceive('getSubscriptionById')
            ->andThrow(eCurring_WC_Exception_ApiClientException::class, 'this is the test exception');
        $apiHelperMock->shouldReceive('createSubscription')
            ->andReturn($subscription_data);
        $ecurringPluginMock->shouldReceive('getApiHelper')
            ->andReturn($apiHelperMock);


        /** @var eCurring_WC_Gateway_Abstract&MockObject $sut */
        $sut = $this->getMockForAbstractClass(eCurring_WC_Gateway_Abstract::class, [], '', false);
        $sut->id = strtolower(get_class($sut));

        expect('update_post_meta')->with(
            $orderId,
            'ecurring_woocommerce_mandate_accepted_date',
            date('Y-m-d H:i:s')
        );
        expect('__')->andReturnFirstArg();
        expect('get_post_meta')->with($orderId, '_ecurring_subscription_plan', true)
            ->andReturn($ecurring_subscription_plan_id);
        expect('add_query_arg');
        $siteUrl = 'localtest.loc';
        expect('get_site_url')
            ->withNoArgs()
            ->andReturn($siteUrl);
        $wcMock = Mockery::mock(stdClass::class);
        $wcMock->shouldReceive('api_request_url')
            ->andReturn($siteUrl);
        expect('WC')
            ->andReturn($wcMock);
        expect('home_url')
            ->andReturn($siteUrl);
        expect('is_plugin_active')
            ->with('polylang/polylang.php')
            ->andReturn(false);

        define('ABSPATH', dirname(__DIR__) .  '/Stubs/');
        define('WOOECUR_PLUGIN_ID', 'woocommerce_ecurring');

        $result = $sut->process_payment($orderId);


        $this->assertSame('success', $result['result']);
    }
}
