<?php

namespace Ecurring\WooEcurringTests\Unit\Subscription;

use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurringTests\TestCase;
use ReflectionMethod;
use function Brain\Monkey\Functions\when;

class RepositoryTest extends TestCase
{
    public function testCreateSubscriptions()
    {
        $subscriptions = (object)[
            'data' => [
                (object)[
                    'id' => "123",
                    'relationships' => (object)[
                        'customer' => (object)[
                            'data' => (object)[
                                'id' => '42',
                            ],
                        ],
                    ],
                    'links' => [],
                    'attributes' => [],
                ],
                (object)[
                    'id' => "456",
                    'relationships' => (object)[
                        'customer' => (object)[
                            'data' => (object)[
                                'id' => '42',
                            ],
                        ],
                    ],
                    'links' => [],
                    'attributes' => [],
                ],
            ],
        ];
        $customersApi = $this->createMock(Customers::class);

        $sut = $this
            ->getMockBuilder(Repository::class)
            ->setConstructorArgs([$customersApi])
            ->setMethods(['getSubscriptionIds', 'orderSubscriptionExist'])
            ->getMock();

        $sut
            ->expects($this->exactly(2))
            ->method('orderSubscriptionExist')
            ->willReturn(true);

        when('wp_insert_post')->justReturn(1);
        when('add_post_meta')->justReturn(1);

        $createSubscriptions = new ReflectionMethod($sut, 'createSubscriptions');
        $createSubscriptions->setAccessible(true);

        $createSubscriptions->invoke($sut, $subscriptions);
    }
}
