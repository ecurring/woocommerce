<?php

namespace Ecurring\WooEcurringTests\Unit\Api;

use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurringTests\TestCase;
use eCurring_WC_Helper_Api;
use function Brain\Monkey\Functions\when;

class CustomersTest extends TestCase
{
    public function testGetCustomerByIdNotCached()
    {
        $apiResponse = '{"foo":{"bar":"baz"}}';
        $responseDecoded = (object)[
            'foo' => (object)[
                'bar' => 'baz',
            ],
        ];
        $api = $this->createMock(eCurring_WC_Helper_Api::class);

        $sut = new Customers($api);

        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);

        $api
            ->expects($this->once())
            ->method('apiCall')
            ->willReturn($apiResponse);

        self::assertEquals($responseDecoded, $sut->getCustomerById(1));
    }

    public function testGetCustomerByIdCached()
    {
        $api = $this->createMock(eCurring_WC_Helper_Api::class);
        $sut = new Customers($api);

        when('get_transient')->justReturn('foo');

        self::assertSame('foo', $sut->getCustomerById(1));
    }

    public function testGetCustomerSubscriptionsNotCached()
    {
        $apiResponse = '{"foo":{"bar":"baz"}}';
        $responseDecoded = (object)[
            'foo' => (object)[
                'bar' => 'baz',
            ],
        ];
        $api = $this->createMock(eCurring_WC_Helper_Api::class);

        $sut = new Customers($api);

        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);

        $api
            ->expects($this->once())
            ->method('apiCall')
            ->willReturn($apiResponse);

        self::assertEquals($responseDecoded, $sut->getCustomerSubscriptions(1));
    }

    public function testGetCustomerSubscriptionsCached()
    {
        $api = $this->createMock(eCurring_WC_Helper_Api::class);
        $sut = new Customers($api);

        when('get_transient')->justReturn('foo');

        self::assertSame('foo', $sut->getCustomerSubscriptions(1));
    }
}