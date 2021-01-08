<?php

namespace Ecurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurringTests\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use Ecurring\WooEcurring\EventListener\PaymentCompletedEventListener;
use WC_Order;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class PaymentCompletedEventListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;  //to properly count Mockery expectations as assertions.

    public function testInit()
    {

        /** @var ApiClientInterface&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClientInterface::class);

        /** @var CustomerCrudInterface&MockObject $customerCrudMock */
        $customerCrudMock = $this->createMock(CustomerCrudInterface::class);

        $sut = new PaymentCompletedEventListener($apiClientMock, $customerCrudMock);

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
        $localCustomerId = 234;
        $mollieMandateId = 'molliemandateid123';
        $subscriptionId = 'subscription321';
        $mandateAcceptedDate = date('c');

        /** @var WC_Order&MockObject $wcOrderMock */
        $wcOrderMock = $this->createMock(WC_Order::class);
        $wcOrderMock->method('get_id')
            ->willReturn($orderId);
        $wcOrderMock->method('get_meta')
            ->with(SubscriptionCrudInterface::MANDATE_ACCEPTED_DATE_FIELD)
            ->willReturn($mandateAcceptedDate);
        $wcOrderMock->method('get_customer_id')
            ->willReturn($localCustomerId);

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

        /** @var CustomerCrudInterface&MockObject $customerCrudMock */
        $customerCrudMock = $this->createMock(CustomerCrudInterface::class);
        $customerCrudMock->expects($this->once())
            ->method('getMollieMandateId')
            ->willReturn($mollieMandateId);
        $customerCrudMock->expects($this->once())
            ->method('getFlagCustomerNeedsMollieMandate')
            ->willReturn(true);

        $sut = new PaymentCompletedEventListener($apiClientMock, $subscriptionCrudMock, $customerCrudMock);

        $sut->onPaymentComplete($orderId);
    }
}
