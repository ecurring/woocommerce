<?php

namespace eCurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\EventListener\MolliePaymentEventListener;
use eCurring\WooEcurringTests\TestCase;
use eCurring_WC_Helper_Data;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use function Brain\Monkey\Functions\expect;

class MolliePaymentEventListenerTest extends TestCase {

	use MockeryPHPUnitIntegration;

	public function testInit(){

		/** @var ApiClient&MockObject $apiClientMock */
		$apiClientMock = $this->createMock( ApiClient::class);

		/** @var eCurring_WC_Helper_Data&MockObject $dataHelperMock */
		$dataHelperMock = $this->createMock(eCurring_WC_Helper_Data::class);

		$sut = new MolliePaymentEventListener($apiClientMock, $dataHelperMock);

		expect('add_action')
		->once()
		->with('mollie-payments-for-woocommerce_payment_created', [$sut, 'onMolliePaymentCreated']);

		$sut->init();
	}
}
