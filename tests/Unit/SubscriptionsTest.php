<?php

namespace eCurring\WooEcurringTests\Unit;

use eCurring\WooEcurring\Subscriptions;
use eCurring\WooEcurringTests\TestCase;

class SubscriptionsTest extends TestCase
{
    public function testGetSubscriptions()
    {
        $subscriptions = new Subscriptions();

        self::assertSame(1, count($subscriptions->getSubscriptions()));
    }
}
