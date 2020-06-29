<?php

namespace eCurring\WooEcurringTests;

use Brain\Monkey;
use Mockery;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();
        Monkey\setUp();
    }

    public function tearDown()
    {
        Mockery::close();
        Monkey\tearDown();
        parent::tearDown();
    }
}
