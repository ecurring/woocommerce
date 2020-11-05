<?php

namespace Ecurring\WooEcurringTests\Unit;

use Ecurring\WooEcurring\EnvironmentChecker\EnvironmentChecker;
use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class EnvironmentCheckerTest extends TestCase {

	use MockeryPHPUnitIntegration; //to count Mockery expectations properly as assertions

    public function testCheckEnvironmentCaseEverythingOk()
    {
        $sut = new EnvironmentChecker(PHP_VERSION, '4.0');

        expect('extension_loaded')
            ->with('json')
            ->andReturn(true);

        expect('get_option')
            ->with('active_plugins')
            ->andReturn([]);

        expect('apply_filters')
            ->with('active_plugins', [])
            ->andReturn(['woocommerce/woocommerce.php']);

        when('admin_url')
            ->justReturn('');

        when('__')
            ->returnArg(1);

        if(! defined('M4W_FILE')){
            define('M4W_FILE', '');
        }

        $molliePluginBasename = 'mollie-for-woocommerce/mollie-for-woocommerce.php';

        expect('plugin_basename')
            ->with(M4W_FILE)
            ->andReturn($molliePluginBasename);

        expect('is_plugin_active')
            ->with($molliePluginBasename)
            ->andReturn(true);

        expect('get_plugin_data')
            ->with(M4W_FILE)
            ->andReturn(['Version' => '6.0.0']);

        $this->assertTrue($sut->checkEnvironment(), 'EnvironmentChecker test false negative.');
        $this->assertSame($sut->getErrors(), [], 'Errors returned after successful environment check.');
    }

    public function testCheckEnvironmentCasePhpVersionLessThenRequired()
    {
        $sut = new EnvironmentChecker('7.2', '4.0');

        expect('phpversion')
            ->andReturn('7.1');

        when('__')
            ->returnArg(1);

        $this->assertFalse($sut->checkEnvironment(), 'EnvironmentChecker test false positive.');

        $errors = $sut->getErrors();
        $stringFound = false;

        foreach ($errors as $errorMessage) {
            if(stristr($errorMessage, 'update your PHP')){
                $stringFound = true;
                break;
            }
        }

        $this->assertTrue($stringFound, 'Not found expected message about PHP update required.');

    }

    public function testCheckEnvironmentCaseNoJsonExtension()
    {
        $sut = new EnvironmentChecker('7.2', '4.0');

        expect('phpversion')
            ->andReturn('7.2');

        expect('get_option')
            ->with('active_plugins')
            ->andReturn([]);

        expect('apply_filters')
            ->with('active_plugins', [])
            ->andReturn(['woocommerce/woocommerce.php']);

        when('admin_url')
            ->justReturn('');

        when('esc_url')
            ->returnArg(1);

        when('__')
            ->returnArg(1);

        when('esc_html__')
            ->returnArg(1);

        if(! defined('M4W_FILE')){
            define('M4W_FILE', '');
        }

        $molliePluginBasename = 'woo-ecurring/woo-ecurring.php';

        expect('plugin_basename')
            ->with(M4W_FILE)
            ->andReturn('woo-ecurring/woo-ecurring.php');

        expect('is_plugin_active')
            ->with($molliePluginBasename)
            ->andReturn(true);

        expect('get_plugin_data')
            ->with(M4W_FILE)
            ->andReturn(['Version' => '6.0.0']);

        expect('extension_loaded')
            ->with('json')
            ->andReturn(false);

        $this->assertFalse($sut->checkEnvironment(), 'EnvironmentChecker test false positive.');

        $errors = $sut->getErrors();
        $stringFound = false;



        foreach ($errors as $errorMessage) {
            if(stristr($errorMessage, 'requires the JSON extension for PHP')){
                $stringFound = true;
                break;
            }
        }

        $this->assertTrue($stringFound, 'Not found expected message about JSON PHP extension required.');
    }
}
