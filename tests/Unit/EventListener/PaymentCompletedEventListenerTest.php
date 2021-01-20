<?php

namespace Ecurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherInterface;
use Ecurring\WooEcurringTests\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use Ecurring\WooEcurring\EventListener\PaymentCompletedEventListener;
use WC_DateTime;
use WC_Order;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * @coversDefaultClass \Ecurring\WooEcurring\EventListener\PaymentCompletedEventListener
 */
class PaymentCompletedEventListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;  //to properly count Mockery expectations as assertions.

    /**
     * @covers
     */
    public function testInit()
    {

        /** @var ApiClientInterface&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClientInterface::class);

        /** @var CustomerCrudInterface&MockObject $customerCrudMock */
        $customerCrudMock = $this->createMock(CustomerCrudInterface::class);

        $repositoryMock = $this->createMock(Repository::class);

        $subscriptionsApiClientMock = $this->createMock(Subscriptions::class);
        $subscriptionsStatusSwitcherMock = $this->createMock(SubscriptionStatusSwitcherInterface::class);

        $sut = new PaymentCompletedEventListener(
            $apiClientMock,
            $subscriptionsApiClientMock,
            $customerCrudMock,
            $subscriptionsStatusSwitcherMock,
            $repositoryMock
        );

        expect('add_action')
            ->once()
            ->with(
                'woocommerce_payment_complete',
                [$sut, 'onPaymentComplete']
            );

        $sut->init();
    }

    /**
     * @covers
     */
    public function testOnPaymentCompleted()
    {
        //Prevent calling static eCurring_WC_Plugin::debug() method.
        $pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $pluginMock->shouldReceive('debug')
            ->with(
                Mockery::on(
                    function($message){
                        return stristr($message, 'Payment completed for order') &&
                            stristr($message, 'trying to activate it.');
                    }
                )
            );

        $orderId = 123;
        $localCustomerId = 234;
        $mollieMandateId = 'molliemandateid123';
        $subscriptionId = 'subscription321';


        $subscriptionCreatedDateMock = $this->createMock(WC_DateTime::class);

        /** @var WC_Order&MockObject $wcOrderMock */
        $wcOrderMock = $this->createMock(WC_Order::class);
        $wcOrderMock->method('get_id')
            ->willReturn($orderId);
        $wcOrderMock->method('get_customer_id')
            ->willReturn($localCustomerId);
        $wcOrderMock->method('get_date_created')
            ->willReturn($subscriptionCreatedDateMock);

        when('wc_get_order')
            ->justReturn($wcOrderMock);

        $repositoryMock = $this->createMock(Repository::class);
        $repositoryMock->expects($this->once())
            ->method('findSubscriptionIdByOrderId')
            ->with($orderId)
            ->willReturn($subscriptionId);

        /** @var ApiClientInterface&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClientInterface::class);

        $subscriptionApiClientMock = $this->createMock(Subscriptions::class);
        $subscriptionApiClientMock->method('activate')
            ->with($subscriptionId, $subscriptionCreatedDateMock);

        /** @var CustomerCrudInterface&MockObject $customerCrudMock */
        $customerCrudMock = $this->createMock(CustomerCrudInterface::class);
        $customerCrudMock->expects($this->once())
            ->method('getMollieMandateId')
            ->willReturn($mollieMandateId);
        $customerCrudMock->expects($this->once())
            ->method('getFlagCustomerNeedsMollieMandate')
            ->willReturn(true);

        $subscriptionStatusSwitcherMock = $this->createMock(SubscriptionStatusSwitcherInterface::class);

        $sut = new PaymentCompletedEventListener(
            $apiClientMock,
            $subscriptionApiClientMock,
            $customerCrudMock,
            $subscriptionStatusSwitcherMock,
            $repositoryMock
        );

        $sut->onPaymentComplete($orderId);
    }
}
