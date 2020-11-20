<?php

namespace Ecurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use Ecurring\WooEcurringTests\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;

use function Brain\Monkey\Functions\expect;

use Ecurring\WooEcurring\EventListener\PaymentCompletedEventListener;
use function Brain\Monkey\Functions\when;

class PaymentCompletedEventListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;  //to properly count Mockery expectations as assertions.

    public function testInit()
    {

        /** @var ApiClientInterface&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClientInterface::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        $sut = new PaymentCompletedEventListener($apiClientMock, $subscriptionCrudMock);

        expect('add_action')
            ->once()
            ->with(
                'woocommerce_payment_complete',
                [$sut, 'onPaymentComplete']
            );

        $sut->init();
    }

    public function testOnMandateCreated()
    {
        //Prevent calling static eCurring_WC_Plugin::debug() method.
        $pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $pluginMock->shouldReceive('debug');

        $orderId = 123;
        $subscriptionId = 'subscription321';
        $mandateAcceptedDate = date('c');

        /** @var WC_Order&MockObject $wcOrderMock */
        $wcOrderMock = $this->createMock(WC_Order::class);
        $wcOrderMock->method('get_id')
            ->willReturn($orderId);

        $wcOrderMock->method('get_meta')
            ->with(SubscriptionCrudInterface::MANDATE_ACCEPTED_DATE_FIELD)
            ->willReturn($mandateAcceptedDate);

        when('wc_get_order')
            ->justReturn($wcOrderMock);

        /** @var ApiClientInterface&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClientInterface::class);
        $apiClientMock->expects($this->once())
            ->method('activateSubscription')
            ->with($subscriptionId, $mandateAcceptedDate);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);
        $subscriptionCrudMock->expects($this->once())
            ->method('getSubscriptionIdByOrder')
            ->with($wcOrderMock)
            ->willReturn($subscriptionId);

        $sut = new PaymentCompletedEventListener($apiClientMock, $subscriptionCrudMock);

        $sut->onPaymentComplete($orderId);
    }
}
