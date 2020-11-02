<?php

namespace Ecurring\WooEcurringTests\Unit;

use Ecurring\WooEcurring\EnvironmentChecker\EnvironmentChecker;
use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use function Brain\Monkey\Functions\expect;

class EnvironmentCheckerTest extends TestCase {

	use MockeryPHPUnitIntegration; //to count Mockery expectations properly as assertions

    /**
     * Skip tests from this class.
     *
     * @todo fix tests and remove this.
     */
    public function setUp()
    {
        $this->markTestIncomplete();
    }

	/**
	 * Test if isMolliePluginActive functions correctly checks for that plugin and returns correct result.
	 *
	 * @dataProvider isMollieActiveDataProvider
	 */
	public function testIsMolliePluginActive($isActive){

	    if($isActive){
           define('M4W_FILE', '/some/path/to/plugin_dir/plugin.php');
        }

	    expect('plugin_basename')
            ->once()
            ->andReturn('plugin_dir/plugin.php');

		expect('is_plugin_active')
			->once()
			->andReturn($isActive);

		$sut = new EnvironmentChecker();

		$this->assertSame($isActive, $sut->checkMollieIsActive());
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

        $this->assertSame($isMinimal, $sut->checkMollieVersion());
    }

    public function isMinimalVersionDataProvider()
    {
        return [
            ['5.9.0', false],
            ['6.0.0', true],
        ];
    }
}
