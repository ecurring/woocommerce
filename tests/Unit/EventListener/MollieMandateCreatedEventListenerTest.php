<?php

namespace Ecurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\EventListener\MollieMandateCreatedEventListener;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use Ecurring\WooEcurringTests\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class MollieMandateCreatedEventListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInit()
    {

        /** @var ApiClient&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClient::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        /** @var CustomerCrudInterface&MockObject $customerCrudMock */
        $customerCrudMock = $this->createMock(CustomerCrudInterface::class);

        $sut = new MollieMandateCreatedEventListener($apiClientMock, $subscriptionCrudMock, $customerCrudMock);

        expect('add_action')
        ->once()
        ->with(
            'mollie-payments-for-woocommerce_after_mandate_created',
            [$sut, 'onMollieMandateCreated'],
            10,
            4
        );

        $sut->init();
    }

    public function testOnMollieMandateCreated()
    {
        //Prevent calling static eCurring_WC_Plugin::debug() method.
        $pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $pluginMock->shouldReceive('debug');

        $localUserId = 123;
        $ecurringCustomerId = 'customerid324';
        $ecurringSubscriptionId = 'ecurringsubscriptionid3463';
        $mollieCustomerId = 'molliecustomerid123';
        $mollieMandateId = 'molliemandateid987';

        /** @var ApiClient&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClient::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        /** @var CustomerCrudInterface&MockObject $customerCrudMock */
        $customerCrudMock = $this->createMock(CustomerCrudInterface::class);
        $customerCrudMock->expects($this->once())
            ->method('getEcurringCustomerId')
            ->with($localUserId)
            ->willReturn('');

        $customerCrudMock->expects($this->once())
            ->method('saveMollieMandateId')
            ->with($localUserId, $mollieMandateId);

        $customerCrudMock->expects($this->once())
            ->method('saveEcurringCustomerId')
            ->with($localUserId, $ecurringCustomerId);

        $sut = new MollieMandateCreatedEventListener($apiClientMock, $subscriptionCrudMock, $customerCrudMock);
        $orderMock = $this->createMock(WC_Order::class);
        $orderMock->method('get_customer_id')
            ->willReturn($localUserId);

        $orderItemProductMock = $this->createMock(WC_Order_Item_Product::class);
        $orderItemMock = $this->createMock(WC_Order_Item::class);

        /** @var WC_Order&MockObject $orderMock */
        $orderMock->expects($this->once())
            ->method('get_items')
            ->willReturn(
                [
                    $orderItemMock,
                    $orderItemProductMock,
                ]
            );
        $orderMock->expects($this->once())
            ->method('get_billing_first_name')
            ->willReturn('Name');

        $productMock = $this->createMock(WC_Product::class);

        $subscriptionCrudMock->expects($this->once())
            ->method('getProductSubscriptionId')
            ->with($productMock)
            ->willReturn($ecurringSubscriptionId);

        $orderItemProductMock->expects($this->once())
            ->method('get_product')
            ->willReturn($productMock);

        when('add_query_arg')
            ->justReturn('');

        when('home_url')
            ->justReturn('');

        when('get_user_meta')->justReturn(0);

        $apiClientMock->expects($this->once())
            ->method('createSubscription')
            ->with(
                $ecurringCustomerId,
                $ecurringSubscriptionId,
                ''
            )
            ->willReturn([]);

        $apiClientMock->expects($this->once())
            ->method('createCustomer')
            ->willReturn(['data' => ['id' => $ecurringCustomerId]]);

        $sut->onMollieMandateCreated(null, $orderMock, $mollieCustomerId, $mollieMandateId);
    }
}
