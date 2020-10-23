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
    public function isMollieActiveDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /** @dataProvider isMinimalVersionDataProvider */
    public function testIsMollieMinimalVersion($mollieCurrentVersion, $isMinimal)
    {
        if (!defined('M4W_FILE')) {
            define('M4W_FILE', 'foo/bar.baz');
        }

        $sut = new EnvironmentChecker();

        expect('get_plugin_data')
            ->once()
            ->andReturn([
                'Version' => $mollieCurrentVersion,
            ]);

        $this->assertSame($isMinimal, $sut->isMollieMinimalVersion());
    }

    public function isMinimalVersionDataProvider()
    {
        return [
            ['5.9.0', false],
            ['6.0.0', true],
        ];
    }
}
