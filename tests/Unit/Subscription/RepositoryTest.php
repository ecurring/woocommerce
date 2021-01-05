<?php

namespace Ecurring\WooEcurringTests\Unit\Subscription;

use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;


class RepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
