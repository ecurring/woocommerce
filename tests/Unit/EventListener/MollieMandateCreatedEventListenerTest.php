<?php

namespace Ecurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Customer\CustomerCrudInterface;
use Ecurring\WooEcurring\EventListener\MollieMandateCreatedEventListener;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;
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

/**
 * @coversDefaultClass \Ecurring\WooEcurring\EventListener\MollieMandateCreatedEventListener
 */
class MollieMandateCreatedEventListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @covers
     */
    public function testInit()
    {

        /** @var ApiClient&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClient::class);

        /** @var CustomerCrudInterface&MockObject $customerCrudMock */
        $customerCrudMock = $this->createMock(CustomerCrudInterface::class);
        $subscriptionsApiClientMock = $this->createMock(Subscriptions::class);
        $repositoryMock = $this->createMock(Repository::class);

        $sut = new MollieMandateCreatedEventListener($apiClientMock, $subscriptionsApiClientMock, $repositoryMock, $customerCrudMock);

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

    /**
     * @covers
     */
    public function testOnMollieMandateCreated()
    {
        //Prevent calling static eCurring_WC_Plugin::debug() method.
        $pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $pluginMock->shouldReceive('debug');

        $localUserId = 123;
        $ecurringCustomerId = 'customerid324';
        $ecurringSubscriptionPlanId = 'ecurringsubscriptionplan324324';
        $mollieCustomerId = 'molliecustomerid123';
        $mollieMandateId = 'molliemandateid987';
        $orderId = 456;
        $siteUrl = 'http://example.com';

        /** @var ApiClient&MockObject $apiClientMock */
        $apiClientMock = $this->createMock(ApiClient::class);
        $apiClientMock->expects($this->once())
            ->method('createCustomer')
            ->willReturn([
                'data' => [
                    'id' => $ecurringCustomerId,
                ]
            ]);
        $subscriptionMock = $this->createMock(SubscriptionInterface::class);

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

        when('get_site_url')
            ->justReturn($siteUrl);
        when('get_current_blog_id')
            ->justReturn(1);

        $subscriptionAttributes = [
            'metadata' => json_encode([
                'source' => 'WooCommerce',
                'shop_url' => $siteUrl,
                'order_id' => $orderId,
            ]),
        ];

        $subscriptionsApiClientMock = $this->createMock(Subscriptions::class);
        $subscriptionsApiClientMock->expects($this->once())
            ->method('create')
            ->with(
                $ecurringCustomerId,
                $ecurringSubscriptionPlanId,
                $subscriptionAttributes
            )->willReturn($subscriptionMock);

        $repositoryMock = $this->createMock(Repository::class);

        $repositoryMock->expects($this->once())
            ->method('insert')
            ->with($subscriptionMock, $orderId);

        $sut = new MollieMandateCreatedEventListener($apiClientMock, $subscriptionsApiClientMock, $repositoryMock, $customerCrudMock);
        $orderMock = $this->createMock(WC_Order::class);
        $orderMock->method('get_customer_id')
            ->willReturn($localUserId);
        $orderMock->expects($this->any())
            ->method('get_id')
            ->willReturn($orderId);

        $orderItemProductMock = $this->createMock(WC_Order_Item_Product::class);
        $orderItemMock = $this->createMock(WC_Order_Item::class);

        /** @var WC_Order&MockObject $orderMock */
        $orderMock->expects($this->any())
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
        $productMock->method('get_meta')
            ->with('_ecurring_subscription_plan')
            ->willReturn($ecurringSubscriptionPlanId);

        $orderItemProductMock->expects($this->once())
            ->method('get_product')
            ->willReturn($productMock);

        when('add_query_arg')
            ->justReturn('');

        when('home_url')
            ->justReturn('');

        when('get_user_meta')->justReturn(0);

        $sut->onMollieMandateCreated(null, $orderMock, $mollieCustomerId, $mollieMandateId);
    }
}
