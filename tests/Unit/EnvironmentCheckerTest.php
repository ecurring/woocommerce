<?php

namespace Ecurring\WooEcurringTests\Unit;

use Ecurring\WooEcurring\EnvironmentChecker\EnvironmentChecker;
use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;
use function Patchwork\redefine;

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

    public function testCheckEnvironmentCaseWoocommerceIsInactive()
    {
        $sut = new EnvironmentChecker('7.2', '4.0');

        expect('get_option')
            ->with('active_plugins')
            ->andReturn([]);

        expect('apply_filters')
            ->with('active_plugins', [])
            ->andReturn([]);

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

        $molliePluginBasename = 'mollie-for-woocommerce/mollie-for-woocommerce.php';

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
            ->andReturn(true);

        expect('phpversion')
            ->andReturn('7.2');

        $this->assertFalse($sut->checkEnvironment(), 'EnvironmentChecker test false positive.');

        $errors = $sut->getErrors();
        $stringFound = false;

        foreach ($errors as $errorMessage) {
            if(stristr($errorMessage, 'install and activate') && stristr($errorMessage, 'WooCommerce')){
                $stringFound = true;
                break;
            }
        }

        $this->assertTrue($stringFound, 'Not found expected message about WooCommerce not active.');
    }

    public function testCheckEnvironmentCaseWoocommerceVersionTooLow()
    {
        $minRequiredPhpVersion = '7.2';
        $minRequiredWcVersion = '4.0';
        $actualPhpVersion = '7.2';

        expect('phpversion')
            ->andReturn($actualPhpVersion);

        $sut = new EnvironmentChecker($minRequiredPhpVersion, $minRequiredWcVersion );

        redefine('version_compare', function ($version1) use ($actualPhpVersion) {
            //return true for PHP version check, false for WC version check
            return $version1 === $actualPhpVersion;
        });

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

        $molliePluginBasename = 'mollie-for-woocommerce/mollie-for-woocommerce.php';

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
            ->andReturn(true);

        $this->assertFalse($sut->checkEnvironment(), 'EnvironmentChecker test false positive.');


        $errors = $sut->getErrors();
        $stringFound = false;

        foreach ($errors as $errorMessage) {
            if(stristr($errorMessage, 'update') && stristr($errorMessage, 'WooCommerce')){
                $stringFound = true;
                break;
            }
        }

        $this->assertTrue($stringFound, 'Not found expected message about WooCommerce update required.');
    }

    public function testCheckEnvironmentCaseMollieIsInactive()
    {
        $sut = new EnvironmentChecker('7.2', '4.0');

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

        $molliePluginBasename = 'mollie-for-woocommerce/mollie-for-woocommerce.php';

        expect('plugin_basename')
            ->with(M4W_FILE)
            ->andReturn('woo-ecurring/woo-ecurring.php');

        expect('is_plugin_active')
            ->with($molliePluginBasename)
            ->andReturn(false);

        expect('extension_loaded')
            ->with('json')
            ->andReturn(true);

        expect('phpversion')
            ->andReturn('7.2');

        $this->assertFalse($sut->checkEnvironment(), 'EnvironmentChecker test false positive.');

        $errors = $sut->getErrors();
        $stringFound = false;

        foreach ($errors as $errorMessage) {
            if(stristr($errorMessage, 'install and activate') && stristr($errorMessage, 'Mollie Payments')){
                $stringFound = true;
                break;
            }
        }

        $this->assertTrue($stringFound, 'Not found expected message about Mollie Payments plugin is not active.');
    }

    public function testCheckEnvironmentCaseMollieVersionTooLow()
    {
        $minRequiredPhpVersion = '7.2';
        $minRequiredWcVersion = '4.0';
        $actualMollieVersion = '5.9.10';
        $actualPhpVersion = $minRequiredPhpVersion;

        expect('phpversion')
            ->andReturn($actualPhpVersion);

        $sut = new EnvironmentChecker($minRequiredPhpVersion, $minRequiredWcVersion );

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

        $molliePluginBasename = 'mollie-for-woocommerce/mollie-for-woocommerce.php';

        expect('plugin_basename')
            ->with(M4W_FILE)
            ->andReturn('woo-ecurring/woo-ecurring.php');

        expect('is_plugin_active')
            ->with($molliePluginBasename)
            ->andReturn(true);

        expect('get_plugin_data')
            ->with(M4W_FILE)
            ->andReturn(['Version' => $actualMollieVersion]);

        expect('extension_loaded')
            ->with('json')
            ->andReturn(true);

        $this->assertFalse($sut->checkEnvironment(), 'EnvironmentChecker test false positive.');


        $errors = $sut->getErrors();
        $stringFound = false;

        foreach ($errors as $errorMessage) {
            if(stristr($errorMessage, 'update') && stristr($errorMessage, 'Mollie Payments')){
                $stringFound = true;
                break;
            }
        }

        $this->assertTrue($stringFound, 'Not found expected message about Mollie plugin update required.');
    }
}
