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
use Ecurring\WooEcurring\EventListener\MollieRecurringPaymentCreatedEventListener;

class MollieRecurringPaymentCreatedEventListenerTest extends TestCase {

	use MockeryPHPUnitIntegration;	//to properly count Mockery expectations as assertions.

	public function testInit(){

		/** @var ApiClientInterface&MockObject $apiClientMock */
		$apiClientMock = $this->createMock(ApiClientInterface::class);

		/** @var SubscriptionCrudInterface&MockObject $subscriptionCrudMock */
		$subscriptionCrudMock = $this->createMock(SubscriptionCrudInterface::class);

		$sut = new MollieRecurringPaymentCreatedEventListener($apiClientMock, $subscriptionCrudMock);

		expect('add_action')
			->once()
			->with(
				'mollie-payments-for-woocommerce_after_mandate_created',
				[$sut, 'onMandateCreated'],
				10,
				3
			);

		$sut->init();
	}

	public function testOnMandateCreated()
	{
		//Prevent calling static eCurring_WC_Plugin::debug() method.
		$pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
		$pluginMock->shouldReceive('debug');


		$orderId = 123;
		$subscriptionId = 'subscription321';
		$mandateCode = 'somemandatecode456';
		$mandateAcceptedDate = date('c');

		/** @var WC_Order&MockObject $wcOrderMock */
		$wcOrderMock = $this->createMock( WC_Order::class);
		$wcOrderMock->method('get_id')
			->willReturn($orderId);

		$wcOrderMock->method('get_meta')
			->with(SubscriptionCrudInterface::MANDATE_ACCEPTED_DATE_FIELD)
			->willReturn($mandateAcceptedDate);

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

		$sut = new MollieRecurringPaymentCreatedEventListener($apiClientMock, $subscriptionCrudMock);

		$sut->onMandateCreated(false, $wcOrderMock, $mandateCode);
	}
}
