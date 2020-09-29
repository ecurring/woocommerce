<?php

namespace eCurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\EventListener\MolliePaymentEventListener;
use Ecurring\WooEcurring\Subscription\SubscriptionCrud;
use eCurring\WooEcurringTests\TestCase;
use eCurring_WC_Helper_Data;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use function Brain\Monkey\Functions\expect;

class MolliePaymentEventListenerTest extends TestCase {

	use MockeryPHPUnitIntegration;

	public function testInit(){

		/** @var ApiClient&MockObject $apiClientMock */
		$apiClientMock = $this->createMock( ApiClient::class);

		/** @var eCurring_WC_Helper_Data&MockObject $dataHelperMock */
		$dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);

		/** @var SubscriptionCrud&MockObject $subscriptionCrudMock */
		$subscriptionCrudMock = $this->createMock(SubscriptionCrud::class);

		$sut = new MolliePaymentEventListener($apiClientMock, $dataHelperMock, $subscriptionCrudMock);

		expect('add_action')
		->once()
		->with('mollie-payments-for-woocommerce_payment_created', [$sut, 'onMolliePaymentCreated']);

		$sut->init();
	}

	public function testOnMollieSubscription()
	{
		/** @var ApiClient&MockObject $apiClientMock */
		$apiClientMock = $this->createMock(ApiClient::class);

		/** @var eCurring_WC_Helper_Data&MockObject $dataHelperMock */
		$dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);

		/** @var SubscriptionCrud&MockObject $subscriptionCrudMock */
		$subscriptionCrudMock = $this->createMock(SubscriptionCrud::class);

		$sut = new MolliePaymentEventListener($apiClientMock, $dataHelperMock, $subscriptionCrudMock);
		$orderMock = $this->createMock( WC_Order::class);

		$orderItemProductMock = $this->createMock( WC_Order_Item_Product::class);
		$orderItemMock = $this->createMock( WC_Order_Item::class);
		$orderMock->expects($this->once())
			->method('get_items')
			->willReturn(
				[
					$orderItemMock,
					$orderItemProductMock
				]
			);

		$productMock = $this->createMock( WC_Product::class);
		$ecurringSubscriptionPlan = 'somesubscriptionplan';
		$productMock->expects($this->once())
			->method('get_meta')
			->with('_ecurring_subscription_plan', true)
			->willReturn($ecurringSubscriptionPlan);

		$productMock->expects($this->once())
			->method('meta_exists')
			->with('_ecurring_subscription_plan')
			->willReturn(true);

		$productMock->expects($this->once())
			->method('get_meta')
			->with('_ecurring_subscription_plan')
			->willReturn($ecurringSubscriptionPlan);

		$orderItemProductMock->expects($this->once())
			->method('get_product')
			->willReturn($productMock);

		$ecurringCustomerId = 'customerid324';
		$dataHelperMock->expects($this->once())
			->method('getUsereCurringCustomerId')
			->willReturn($ecurringCustomerId);

		expect('add_query_arg')
			->twice()
			->andReturn('');

		expect('home_url')
			->twice()
			->andReturn('');

		$apiClientMock->expects($this->once())
			->method('createSubscription')
			->with(
				$ecurringCustomerId,
				$ecurringSubscriptionPlan,
				'',
				''
			)
			->willReturn([]);

		$sut->onMolliePaymentCreated(null, $orderMock);
	}
}
