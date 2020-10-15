<?php

namespace Ecurring\WooEcurringTests\Unit;

use Ecurring\WooEcurring\EnvironmentChecker;
use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use function Brain\Monkey\Functions\expect;

class EnvironmentCheckerTest extends TestCase {

	use MockeryPHPUnitIntegration; //to count Mockery expectations properly as assertions

	/**
	 * Test if isMolliePluginActive functions correctly checks for that plugin and returns correct result.
	 *
	 * @dataProvider isMollieActiveDataProvider
	 */
	public function testIsMolliePluginActive($isActive){
		expect('is_plugin_active')
			->once()
			->andReturn($isActive);

		$sut = new EnvironmentChecker();

		$this->assertSame($isActive, $sut->isMollieActive());
	}

	/**
	 * Return possible options for Mollie Payments for Woocommerce plugin states: true for active, false for inactive.
	 */
	public function isMollieActiveDataProvider() {
		return [
			[true],
			[false]
		];
	}


}
