<?php

namespace eCurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\EventListener\MolliePaymentEventListener;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring\WooEcurringTests\TestCase;
use eCurring_WC_Helper_Data;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class MolliePaymentEventListenerTest extends TestCase {

	use MockeryPHPUnitIntegration;

	public function testInit(){

		/** @var ApiClient&MockObject $apiClientMock */
		$apiClientMock = $this->createMock( ApiClient::class);

		/** @var eCurring_WC_Helper_Data&MockObject $dataHelperMock */
		$dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);

		/** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
		$subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

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

		/** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
		$subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

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

		$apiClientMock->expects($this->once())
			->method('createSubscription')
			->with(
				$ecurringCustomerId,
				$ecurringSubscriptionId,
				'',
				''
			)
			->willReturn([]);

		$sut->onMolliePaymentCreated(null, $orderMock);
	}
}
