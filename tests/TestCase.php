<?php

namespace Ecurring\WooEcurringTests;

use Brain\Monkey;
use Mockery;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp():void
    {
        parent::setUp();
        Monkey\setUp();
    }

    public function tearDown():void
    {
        Mockery::close();
        Monkey\tearDown();
        parent::tearDown();
    }
}
