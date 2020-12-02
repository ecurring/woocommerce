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
		//Prevent calling static eCurring_WC_Plugin::debug() method.
		$pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
		$pluginMock->shouldReceive('debug');


		$orderId = 123;
		$subscriptionId = 'subscription321';
		$mandateAcceptedDate = date('c');

		$wcOrderMock = $this->createMock( WC_Order::class);
		$wcOrderMock->method('get_id')
			->willReturn($orderId);

		$wcOrderMock->method('get_meta')
			->with(SubscriptionCrudInterface::MANDATE_ACCEPTED_DATE_FIELD)
			->willReturn($mandateAcceptedDate);

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
