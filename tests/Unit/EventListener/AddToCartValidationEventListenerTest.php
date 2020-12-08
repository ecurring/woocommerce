<?php

namespace eCurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\EventListener\AddToCartValidationEventListener;
use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring\WooEcurringTests\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Product;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class AddToCartValidationEventListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOnAddToCartNotAllowedToAddSecondSubscription()
    {
        $subscriptionId = '456';

        $ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
        $ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
            ->andReturn(true);

        $productId = 123;

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);

        expect('wc_get_product')->with($productId)
            ->andReturn($productMock);
        when('_x')->returnArg();

        expect('get_current_user_id')
            ->andReturn(456);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
        $subscriptionsCrudMock = $this->createConfiguredMock(
            SubscriptionCrudInterface::class,
            ['getProductSubscriptionId' => $subscriptionId]
        );

        expect('wc_add_notice')->once();

        $sut = new AddToCartValidationEventListener($subscriptionsCrudMock);
        $addedToCart = $sut->onAddToCart(true, $productId, 1);

        $this->assertSame(false, $addedToCart);
    }

    public function testOnAddToCartNotAllowedToAddTwoSubscriptions()
    {
        $subscriptionId = '456';

        $productId = 123;

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);

        expect('wc_get_product')->with($productId)
                                ->andReturn($productMock);
        when('_x')->returnArg();

        /** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
        $subscriptionsCrudMock = $this->createConfiguredMock(
            SubscriptionCrudInterface::class,
            ['getProductSubscriptionId' => $subscriptionId]
        );

        expect('get_current_user_id')
            ->andReturn(456);

        $sut = new AddToCartValidationEventListener($subscriptionsCrudMock);

        expect('wc_add_notice')->once();

        $addedToCart = $sut->onAddToCart(true, $productId, 2);

        $this->assertSame(false, $addedToCart);
    }

    public function testOnAddToCartItsAllowedToAddSubscriptionIfNotAddedYet()
    {
        $subscriptionId = '456';

        $ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
        $ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
                       ->andReturn(false);

        $productId = 123;

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);

        expect('wc_get_product')->with($productId)
                                ->andReturn($productMock);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
        $subscriptionsCrudMock = $this->createConfiguredMock(
            SubscriptionCrudInterface::class,
            ['getProductSubscriptionId' => $subscriptionId]
        );

        expect('get_current_user_id')
            ->andReturn(456);

        expect('wc_add_notice')->never();

        when('_x')->returnArg();

        $sut = new AddToCartValidationEventListener($subscriptionsCrudMock);
        $addedToCart = $sut->onAddToCart(true, $productId, 1);

        $this->assertSame(true, $addedToCart);
    }

    public function testOnAddSubscriptionItsNotAllowedToGuestsToAddSubscription()
    {
        $subscriptionId = '456';
        $productId = 123;

        $ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
        $ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
            ->andReturn(false);

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);

        expect('wc_get_product')->with($productId)
            ->andReturn($productMock);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
        $subscriptionsCrudMock = $this->createConfiguredMock(
            SubscriptionCrudInterface::class,
            ['getProductSubscriptionId' => $subscriptionId]
        );

        expect('wc_add_notice')->once();

        expect('get_current_user_id')
            ->andReturn(0);

        when('_x')->returnArg();
        when('wc_get_page_permalink')->justReturn('');

        $sut = new AddToCartValidationEventListener($subscriptionsCrudMock);
        $addedToCart = $sut->onAddToCart(true, $productId, 1);

        $this->assertSame(false, $addedToCart);
    }

    public function testOnAddToCartItsAllowedToAddSimpleProductsIfSubscriptionAdded()
    {
        $ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
        $ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
                       ->andReturn(true);

        $productId = 123;

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);

        expect('wc_get_product')->with($productId)
                                ->andReturn($productMock);

        /** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
        $subscriptionsCrudMock = $this->createConfiguredMock(
            SubscriptionCrudInterface::class,
            ['getProductSubscriptionId' => null]
        );

        expect('wc_add_notice')->never();

        $sut = new AddToCartValidationEventListener($subscriptionsCrudMock);
        $addedToCart = $sut->onAddToCart(true, $productId, 2);

        $this->assertSame(true, $addedToCart);
    }
}
