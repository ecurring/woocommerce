<?php

namespace Ecurring\WooEcurringTests\Unit\Subscription;

use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurringTests\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class RepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider subscriptionsDataProvider
     */
    public function testCreateSubscriptions($subscriptions)
    {
        //Prevent calling static eCurring_WC_Plugin::debug() method.
        $pluginMock = Mockery::mock('alias:eCurring_WC_Plugin');
        $pluginMock->shouldReceive('debug');

        $sut = new Repository();

        when('get_posts')
            ->justReturn([]);

        when('wc_get_orders')
            ->justReturn([11]);

        expect('wp_insert_post')->twice();

        $sut->createSubscriptions($subscriptions);
    }

    public function subscriptionsDataProvider(): array
    {

        $subscription1 = (object) [
            'links' => [
                'self' => '',
            ],
                'type' => 'subscription',
                'id' => 'subscriptionid123',
                'links' => [
                    'self' => '',
                ],
                'attributes' => [
                    'mandate_code' => '',
                    'mandate_accepted' => true,
                ],
                'relationships' => [
                    'subscriptions-plan' => [
                        'data' => [
                            'type' => 'subscription-plan',
                            'id' => 1,
                        ],
                    ],
                ],
                'customer' => [
                    'data' => [
                        'type' => 'customer',
                        'id' => 2,
                    ],
                ],
                'transactions' => [
                    'links' => [
                        'related' => '',
                    ],
                    'data' => [
                        'type' => 'transaction',
                        'id' => '12345-6789-1203-2342',
                    ],
                ],
        ];

        $subscription2 = (object) [
            'links' => [
                'self' => '',
            ],
                'type' => 'subscription',
                'id' => 'subscriptionid456',
                'links' => [
                    'self' => '',
                ],
                'attributes' => [
                    'mandate_code' => '',
                    'mandate_accepted' => true,
                ],
                'relationships' => [
                    'subscriptions-plan' => [
                        'data' => [
                            'type' => 'subscription-plan',
                            'id' => 2,
                        ],
                    ],
                ],
                'customer' => [
                    'data' => [
                        'type' => 'customer',
                        'id' => 3,
                    ],
                ],
                'transactions' => [
                    'links' => [
                        'related' => '',
                    ],
                    'data' => [
                        'type' => 'transaction',
                        'id' => '9876-5432-1293-8573',
                    ],
                ],
        ];

        $subscriptionsSet = (object) [
            'meta' => [
                    'total' => 2,
                ],
            'links' => [
                'self' => '',
                'first' => '',
                'last' => '',
                'prev' => null,
                'next' => null,
            ],
            'data' => [
                $subscription1,
                $subscription2,
            ],
        ];

        return [
            [
                $subscriptionsSet,
            ],
        ];
    }
}
