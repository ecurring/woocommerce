<?php


namespace eCurring\WooEcurringTests\Unit\EventListener;


use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;
use function Brain\Monkey\Functions\expect;
use Ecurring\WooEcurring\EventListener\PaymentCompleteEventListener;

class PaymentCompleteEventListenerTest extends TestCase {

	use MockeryPHPUnitIntegration;	//to properly count Mockery expectations as assertions.

	public function testInit(){

		/** @var ApiClientInterface&MockObject $apiClientMock */
		$apiClientMock = $this->createMock(ApiClientInterface::class);

		/** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
		$subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

		$sut = new PaymentCompleteEventListener($apiClientMock, $subscriptionCrudMock);

		expect('add_action')
			->once()
			->with(
				'woocommerce_payment_complete',
				[$sut, 'onPaymentComplete']
			);

		$sut->init();
	}

	public function testOnPaymentComplete()
	{
		$orderId = 123;
		$subscriptionId = 'subscription321';

		$wcOrderMock = $this->createMock( WC_Order::class);

		expect('wc_get_order')
			->once()
			->with($orderId)
			->andReturn($wcOrderMock);

		/** @var ApiClientInterface&MockObject $apiClientMock */
		$apiClientMock = $this->createMock(ApiClientInterface::class);
		$apiClientMock->expects($this->once())
			->method('activateSubscription');

		/** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
		$subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);
		$subscriptionCrudMock->expects($this->once())
			->method('getSubscriptionIdByOrder')
			->with($wcOrderMock)
			->willReturn($subscriptionId);

		$sut = new PaymentCompleteEventListener($apiClientMock, $subscriptionCrudMock);

		$sut->onPaymentComplete($orderId);
	}
}
