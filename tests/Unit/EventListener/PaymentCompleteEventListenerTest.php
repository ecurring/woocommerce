<?php


namespace eCurring\WooEcurringTests\Unit\EventListener;


use Ecurring\WooEcurring\Api\ApiClientInterface;
use eCurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use function Brain\Monkey\Functions\expect;
use Ecurring\WooEcurring\EventListener\PaymentCompleteEventListener;

class PaymentCompleteEventListenerTest extends TestCase {

	use MockeryPHPUnitIntegration;	//to properly count Mockery expectations as assertions.

	public function testInit(){

		/** @var ApiClientInterface&MockObject $apiClientMock */
		$apiClientMock = $this->createMock(ApiClientInterface::class);

		$sut = new PaymentCompleteEventListener($apiClientMock);

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
		/** @var ApiClientInterface&MockObject $apiClientMock */
		$apiClientMock = $this->createMock(ApiClientInterface::class);
		$apiClientMock->expects($this->once())
			->method('activateSubscription');
		$sut = new PaymentCompleteEventListener($apiClientMock);

		$sut->onPaymentComplete($orderId);
	}
}
