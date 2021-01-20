<?php

namespace Ecurring\WooEcurringTests\Unit\Api;

use Ecurring\WooEcurring\Api\SubscriptionPlans;
use Ecurring\WooEcurringTests\TestCase;
use eCurring_WC_Helper_Api;
use function Brain\Monkey\Functions\when;

/**
 * @coversDefaultClass \Ecurring\WooEcurring\Api\SubscriptionPlans
 */
class SubscriptionPlansTest extends TestCase
{
    /**
     * @covers
     */
    public function testGetSubscriptionPlansNotCached()
    {
        $apiResponse = '{"foo":{"bar":"baz"}}';
        $responseDecoded = (object)[
            'foo' => (object)[
                'bar' => 'baz',
            ],
        ];
        $api = $this->createMock(eCurring_WC_Helper_Api::class);

        $sut = new SubscriptionPlans($api);

        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);

        $api
            ->expects($this->once())
            ->method('apiCall')
            ->willReturn($apiResponse);

        self::assertEquals($responseDecoded, $sut->getSubscriptionPlans());
    }

    /**
     * @covers
     */
    public function testGetSubscriptionPlansCached()
    {
        $api = $this->createMock(eCurring_WC_Helper_Api::class);
        $sut = new SubscriptionPlans($api);

        when('get_transient')->justReturn('foo');

        self::assertSame('foo', $sut->getSubscriptionPlans());
    }
}
