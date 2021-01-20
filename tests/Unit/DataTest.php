<?php

namespace Ecurring\WooEcurringTests\Unit;

use eCurring_WC_Helper_Data;
use Ecurring\WooEcurringTests\TestCase;
use ReflectionMethod;
use WC_Order;

use Brain\Monkey\Functions;

/**
 * @coversDefaultClass
 */
class DataTest extends TestCase
{
    /**
     * @dataProvider provider
     * 
     * @covers
     */
    public function testGetCustomerLanguageReturnsExpectedLanguage($input, $output)
    {
        $order = $this->createMock(WC_Order::class);

        $data = $this->getMockBuilder(eCurring_WC_Helper_Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $getCustomerLanguage = new ReflectionMethod($data, 'getCustomerLanguage');
        $getCustomerLanguage->setAccessible(true);

        $order->expects($this->once())
            ->method('get_customer_id')
            ->willReturn(1);

        Functions\when('get_user_locale')->justReturn($input);

        self::assertSame($output, $getCustomerLanguage->invoke($data, $order));
    }

    public function provider()
    {
        return [
            ['de_DE', 'de'],
            ['nl_BE', 'nl-be'],
            [null, 'en'],
            ['', 'en'],
            ['Klingon', 'en'],
        ];
    }
}
