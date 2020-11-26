<?php

namespace Ecurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\EventListener\MollieMandateCreatedEventListener;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use Ecurring\WooEcurringTests\TestCase;
use eCurring_WC_Helper_Data;
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

        /** @var eCurring_WC_Helper_Data&MockObject $dataHelperMock */
        $dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        $sut = new MollieMandateCreatedEventListener($apiClientMock, $dataHelperMock, $subscriptionCrudMock);

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

        /** @var ApiClient&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClient::class);

        /** @var eCurring_WC_Helper_Data&MockObject $dataHelperMock */
        $dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
        $subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

        $sut = new MollieMandateCreatedEventListener($apiClientMock, $dataHelperMock, $subscriptionCrudMock);
        $orderMock = $this->createMock(WC_Order::class);

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

        $ecurringSubscriptionId = 'ecurringsubscriptionid3463';

        $subscriptionCrudMock->expects($this->once())
            ->method('getProductSubscriptionId')
            ->with($productMock)
            ->willReturn($ecurringSubscriptionId);

        $orderItemProductMock->expects($this->once())
            ->method('get_product')
            ->willReturn($productMock);

        $ecurringCustomerId = 'customerid324';
        $dataHelperMock->expects($this->once())
            ->method('getUsereCurringCustomerId')
            ->willReturn($ecurringCustomerId);

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

        $sut->onMollieMandateCreated(null, $orderMock, '', '');
    }
}
