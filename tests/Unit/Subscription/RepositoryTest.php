<?php

namespace Ecurring\WooEcurringTests\Unit\Subscription;

use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;
use Ecurring\WooEcurringTests\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

use function Brain\Monkey\Functions\expect;

/**
 * @coversDefaultClass \Ecurring\WooEcurring\Subscription\Repository
 */
class RepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @covers
     */
    public function testInsert()
    {
        $pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $pluginMock->shouldReceive('debug')
            ->with(Mockery::on(function ($message) {
                return (bool) stristr($message, 'successfully saved as post');
            }));

        $orderId = 2432;
        $subscriptionId = '5435';
        $insertedPostId = 213;
        $ecurringCustomerId = '8453';
        $customerDetails = [];
        $subscriptionMock = $this->createConfiguredMock(
            SubscriptionInterface::class,
            [
                'getId' => $subscriptionId,
                'getCustomerId' => $ecurringCustomerId,
            ]
        );

        expect('wp_insert_post')
            ->with(
                [
                'post_type' => 'esubscriptions',
                'post_title' => $subscriptionId,
                'post_status' => 'publish',
                ]
            )->andReturn($insertedPostId);

        $subscriptionFactoryMock = $this->createMock(DataBasedSubscriptionFactoryInterface::class);
        $customersApiClientMock = $this->createMock(Customers::class);
        $customersApiClientMock->expects($this->once())
            ->method('getCustomerById')
            ->with($ecurringCustomerId)
            ->willReturn($customerDetails);

        expect('update_post_meta');

        $sut = new Repository($subscriptionFactoryMock, $customersApiClientMock);
        $sut->insert($subscriptionMock, $orderId);
    }
}
