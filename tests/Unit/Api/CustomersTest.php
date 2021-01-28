<?php

namespace Ecurring\WooEcurringTests\Unit\Api;

use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurringTests\TestCase;
use eCurring_WC_Helper_Api;
use function Brain\Monkey\Functions\when;

/**
 * @coversDefaultClass \Ecurring\WooEcurring\Api\Customers
 */
class CustomersTest extends TestCase
{
    /**
     * @covers
     */
    public function testGetCustomerByIdNotCached()
    {
        $apiResponse = '{"foo":{"bar":"baz"}}';
        $responseDecoded = (object)[
            'foo' => (object)[
                'bar' => 'baz',
            ],
        ];
        $api = $this->createMock(eCurring_WC_Helper_Api::class);
        $apiClientMock = $this->createMock(ApiClientInterface::class);

        $sut = new Customers($api, $apiClientMock);

        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);

        $api
            ->expects($this->once())
            ->method('apiCall')
            ->willReturn($apiResponse);

        self::assertEquals($responseDecoded, $sut->getCustomerById(1));
    }

    /**
     * @covers
     */
    public function testGetCustomerByIdCached()
    {
        $api = $this->createMock(eCurring_WC_Helper_Api::class);
        $apiClientMock = $this->createMock(ApiClientInterface::class);
        $sut = new Customers($api, $apiClientMock);

        when('get_transient')->justReturn('foo');

        self::assertSame('foo', $sut->getCustomerById(1));
    }

    /**
     * @covers
     */
    public function testGetCustomerSubscriptionsNotCached()
    {
        $apiResponse = '{"foo":{"bar":"baz"}}';
        $responseDecoded = (object)[
            'foo' => (object)[
                'bar' => 'baz',
            ],
        ];
        $api = $this->createMock(eCurring_WC_Helper_Api::class);

        $apiClientMock = $this->createMock(ApiClientInterface::class);
        $sut = new Customers($api, $apiClientMock);

        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);

        $api
            ->expects($this->once())
            ->method('apiCall')
            ->willReturn($apiResponse);

        self::assertEquals($responseDecoded, $sut->getCustomerSubscriptions(1));
    }

    /**
     * @covers
     */
    public function testGetCustomerSubscriptionsCached()
    {
        $api = $this->createMock(eCurring_WC_Helper_Api::class);
        $apiClientMock = $this->createMock(ApiClientInterface::class);
        $sut = new Customers($api, $apiClientMock);

        when('get_transient')->justReturn('foo');

        self::assertSame('foo', $sut->getCustomerSubscriptions(1));
    }
}
