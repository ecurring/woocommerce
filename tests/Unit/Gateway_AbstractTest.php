<?php


namespace eCurring\WooEcurringTests\Unit;


use eCurring\WooEcurringTests\TestCase;
use eCurring_WC_Gateway_Abstract;
use eCurring_WC_Helper_Api;
use eCurring_WC_Helper_Data;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;

class Gateway_AbstractTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testProcess_payment()
    {
        $orderId = 123;
        $wcOrderMock = Mockery::mock(WC_Order::class);

        $wcOrderMock->shouldReceive('get_id')
            ->andReturn($orderId);
        $ecurringPluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);
        $dataHelperMock->expects($this->once())
            ->method('getWcOrder')
            ->with($orderId)
            ->willReturn($wcOrderMock);
        $ecurringPluginMock->shouldReceive('getDataHelper')
            ->andReturn($dataHelperMock);
        $ecurringPluginMock->shouldReceive('debug');
        $ecurringPluginMock->shouldReceive('addNotice');
        $apiHelperMock = Mockery::mock(eCurring_WC_Helper_Api::class);
        $ecurringPluginMock->shouldReceive('getApiHelper')
            ->andReturn($apiHelperMock);


        /** @var eCurring_WC_Gateway_Abstract&MockObject $sut */
        $sut = $this->getMockForAbstractClass(eCurring_WC_Gateway_Abstract::class, [], '', false);
        $sut->id = strtolower(get_class($sut));

        $sut->process_payment($orderId);
    }
}
