<?php

namespace Ecurring\WooEcurringTests\Unit\EventListener;

use Ecurring\WooEcurring\EventListener\AddToCartValidationEventListener;
use Ecurring\WooEcurringTests\TestCase;
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
        $ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
        $ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
            ->andReturn(true);

        $productId = 123;

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);
        $productMock->method('get_meta')
            ->with('_ecurring_subscription_plan')
            ->willReturn(432);

        expect('wc_get_product')->with($productId)
            ->andReturn($productMock);
        when('_x')->returnArg();

        expect('get_current_user_id')
            ->andReturn(456);

        expect('wc_add_notice')->once();

        $sut = new AddToCartValidationEventListener();
        $addedToCart = $sut->onAddToCart(true, $productId, 1);

        $this->assertSame(false, $addedToCart);
    }

    public function testOnAddToCartNotAllowedToAddTwoSubscriptions()
    {
        $subscriptionId = '456';

        $productId = 123;

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);
        $productMock->method('get_meta')
            ->with('_ecurring_subscription_plan')
            ->willReturn($subscriptionId);

        expect('wc_get_product')->with($productId)
                                ->andReturn($productMock);
        when('_x')->returnArg();

        expect('get_current_user_id')
            ->andReturn(456);

        expect('get_current_user_id')
            ->andReturn(1);

        $sut = new AddToCartValidationEventListener();

        expect('wc_add_notice')->once();

        $addedToCart = $sut->onAddToCart(true, $productId, 2);

        $this->assertSame(false, $addedToCart);
    }

    public function testOnAddToCartItsAllowedToAddSubscriptionIfNotAddedYet()
    {
        $ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
        $ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
                       ->andReturn(false);

        $productId = 123;

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);

        $productMock->method('get_meta')
            ->with('_ecurring_subscription_plan')
            ->willReturn(567);

        expect('wc_get_product')->with($productId)
                                ->andReturn($productMock);

        expect('get_current_user_id')
            ->andReturn(456);

        $sut = new AddToCartValidationEventListener();
        $addedToCart = $sut->onAddToCart(true, $productId, 1);

        $this->assertSame(true, $addedToCart);
    }

    public function testOnAddSubscriptionGuestsNotAllowedToAddSubscriptionToCart()
    {
        $productId = 123;

        $ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
        $ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
            ->andReturn(false);

        /** @var WC_Product&MockObject $productMock */
        $productMock = $this->createMock(WC_Product::class);
        $productMock->method('get_meta')
            ->with('_ecurring_subscription_plan')
            ->willReturn(567);

        expect('wc_get_product')->with($productId)
            ->andReturn($productMock);

        expect('wc_add_notice')->once();

        expect('get_current_user_id')
            ->andReturn(0);

        when('_x')->returnArg();
        when('wc_get_page_permalink')->justReturn('');

        when('wp_kses_post')->returnArg();

        $sut = new AddToCartValidationEventListener();
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

        $productMock->method('get_meta')
            ->with('_ecurring_subscription_plan')
            ->willReturn('');

        expect('wc_get_product')->with($productId)
                                ->andReturn($productMock);

        expect('wc_add_notice')->never();

        when('get_current_user_id')
            ->justReturn(1);
        when('_x')
            ->returnArg(1);

        $sut = new AddToCartValidationEventListener();
        $addedToCart = $sut->onAddToCart(true, $productId, 1);

        $this->assertSame(true, $addedToCart);
    }
}
