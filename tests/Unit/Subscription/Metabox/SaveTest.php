<?php

namespace Ecurring\WooEcurringTests\Unit\Subscription\Metabox;

use DateTime;
use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Subscription\Metabox\Save;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurringTests\TestCase;
use ReflectionMethod;
use function Brain\Monkey\Functions\expect;

class SaveTest extends TestCase
{
    public function testSavePause()
    {
        $postId = 1;
        $subscriptionType = 'resume';
        $subscriptionId = '123';
        $subscriptionsApi = $this->createMock(Subscriptions::class);
        $repository = $this->createMock(Repository::class);

        $sut = $this
            ->getMockBuilder(Save::class)
            ->setConstructorArgs([$subscriptionsApi, $repository])
            ->setMethods(['updateSubscriptionAttributes', 'subscriptionNotAllowed'])
            ->getMock();

        expect('filter_input')
            ->once()
            ->with(INPUT_POST, 'ecurring_subscription', FILTER_SANITIZE_STRING)
            ->andReturn($subscriptionType);

        expect('filter_input')
            ->once()
            ->with(INPUT_POST, 'ecurring_subscription_id', FILTER_SANITIZE_STRING)
            ->andReturn($subscriptionId);

        $sut
            ->expects($this->once())
            ->method('subscriptionNotAllowed')
            ->willReturn(false);

        $response = (object)['foo' => 'bar'];
        $subscriptionsApi
            ->expects($this->once())
            ->method('resume')
            ->with($subscriptionId)
            ->willReturn($response);

        $sut
            ->expects($this->once())
            ->method('updateSubscriptionAttributes')
            ->with($postId, $response);

        $save = new ReflectionMethod($sut, 'save');
        $save->setAccessible(true);

        $save->invoke($sut, $postId);
    }


    public function testSaveResume()
    {
        $postId = 1;
        $subscriptionType = 'pause';
        $subscriptionId = '123';
        $resumeDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
        $subscriptionsApi = $this->createMock(Subscriptions::class);
        $repository = $this->createMock(Repository::class);

        $sut = $this
            ->getMockBuilder(Save::class)
            ->setConstructorArgs([$subscriptionsApi, $repository])
            ->setMethods(
                ['updateSubscriptionAttributes', 'subscriptionNotAllowed', 'setResumeDate']
            )
            ->getMock();

        expect('filter_input')
            ->once()
            ->with(INPUT_POST, 'ecurring_subscription', FILTER_SANITIZE_STRING)
            ->andReturn($subscriptionType);

        expect('filter_input')
            ->once()
            ->with(INPUT_POST, 'ecurring_subscription_id', FILTER_SANITIZE_STRING)
            ->andReturn($subscriptionId);

        $sut
            ->expects($this->once())
            ->method('subscriptionNotAllowed')
            ->willReturn(false);

        $sut
            ->expects($this->once())
            ->method('setResumeDate')
            ->willReturn($resumeDate);

        $response = (object)['foo' => 'bar'];
        $subscriptionsApi
            ->expects($this->once())
            ->method('pause')
            ->with($subscriptionId, $resumeDate)
            ->willReturn($response);

        $sut
            ->expects($this->once())
            ->method('updateSubscriptionAttributes')
            ->with($postId, $response);

        $save = new ReflectionMethod($sut, 'save');
        $save->setAccessible(true);

        $save->invoke($sut, $postId);
    }

}
