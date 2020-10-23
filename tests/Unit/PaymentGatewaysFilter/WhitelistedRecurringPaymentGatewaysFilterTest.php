<?php

namespace Ecurring\WooEcurringTests\Unit\PaymentGatewaysFilter;

use Ecurring\WooEcurring\PaymentGatewaysFilter\WhitelistedRecurringPaymentGatewaysFilter;
use Ecurring\WooEcurringTests\TestCase;
use WC_Payment_Gateway;
use function Patchwork\redefine;

class WhitelistedRecurringPaymentGatewaysFilterTest extends TestCase {

	/**
	 * @dataProvider filterTestDataProvider
	 */
	public function testFilter(
		array $whitelistedClasses,
		array $gatewaysToFilter,
		array $expectedResult
	) {

		//Cannot use `expect()` from brain/monkey package because of endless loop is starting after calling `andReturn()`
		redefine('get_class', function($item){
			if(! is_object($item)){
				return false;
			}

			return $item->whitelisted ? 'Some\Whitelisted\Class\Name' : 'Some\Not\Whitelisted\Class\Name';
		});

		$sut = new WhitelistedRecurringPaymentGatewaysFilter($whitelistedClasses);
		$filtered = $sut->filter($gatewaysToFilter);

		$this->assertSame($expectedResult, array_values($filtered));
	}

	public function filterTestDataProvider() {
		$nonRecurringGateway = $this->createMock( WC_Payment_Gateway::class );
		$nonRecurringGateway->method( 'supports' )
		                    ->with( 'subscriptions' )
		                    ->willReturn( false );

		$nonRecurringGateway->whitelisted = true;


		$recurringGateway = $this->createMock( WC_Payment_Gateway::class );
		$recurringGateway->method( 'supports' )
		                 ->with( 'subscriptions' )
		                 ->willReturn( true );

		$recurringGateway->whitelisted = false;

		$recurringWhitelistedGateway = $this->createMock( WC_Payment_Gateway::class );
		$recurringWhitelistedGateway->method( 'supports' )
		                            ->with( 'subscriptions' )
		                            ->willReturn( true );

		$recurringWhitelistedGateway->whitelisted = true;



		$whitelist = ['Some\Whitelisted\Class\Name'];

		return [
			[
				[],
				[],
				[]
			],
			[
				$whitelist,
				[ $nonRecurringGateway, $recurringGateway, $recurringWhitelistedGateway ],
				[ $recurringWhitelistedGateway ]
			]
		];
	}
}
