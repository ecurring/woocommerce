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

class AddToCartEventListenerTest extends TestCase {

	use MockeryPHPUnitIntegration;

	public function testOnAddToCartNotAllowedToAddSecondSubscription()
	{
		$subscriptionId = '456';

		$ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
		$ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
			->andReturn(true);

		$productId = 123;

		/** @var WC_Product&MockObject $productMock */
		$productMock = $this->createMock( WC_Product::class);

		expect('wc_get_product')->with($productId)
			->andReturn($productMock);
		when('_x')->returnArg();

		/** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
		$subscriptionsCrudMock = $this->createConfiguredMock(
			SubscriptionCrudInterface::class,
			['getProductSubscriptionId' => $subscriptionId]
		);

		$sut = new AddToCartValidationEventListener($subscriptionsCrudMock);

		expect('wc_add_notice')->once();

		$addedToCart = $sut->onAddToCart(true, $productId, 1);

		$this->assertSame(false, $addedToCart);
	}

	public function testOnAddToCartNotAllowedToAddTwoSubscriptions()
	{
		$subscriptionId = '456';

		$productId = 123;

		/** @var WC_Product&MockObject $productMock */
		$productMock = $this->createMock( WC_Product::class);

		expect('wc_get_product')->with($productId)
		                        ->andReturn($productMock);
		when('_x')->returnArg();

		/** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
		$subscriptionsCrudMock = $this->createConfiguredMock(
			SubscriptionCrudInterface::class,
			['getProductSubscriptionId' => $subscriptionId]
		);

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
		$productMock = $this->createMock( WC_Product::class);

		expect('wc_get_product')->with($productId)
		                        ->andReturn($productMock);

		/** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
		$subscriptionsCrudMock = $this->createConfiguredMock(
			SubscriptionCrudInterface::class,
			['getProductSubscriptionId' => $subscriptionId]
		);

		$sut = new AddToCartValidationEventListener($subscriptionsCrudMock);

		expect('wc_add_notice')->never();

		when('_x')->returnArg();

		$addedToCart = $sut->onAddToCart(true, $productId, 1);

		$this->assertSame(true, $addedToCart);
	}

	public function testOnAddToCartItsAllowedToAddSimpleProductsIfSubscriptionAdded()
	{
		$ecurringPlugin = Mockery::mock('alias:eCurring_WC_Plugin');
		$ecurringPlugin->shouldReceive('eCurringSubscriptionIsInCart')
		               ->andReturn(true);

		$productId = 123;

		/** @var WC_Product&MockObject $productMock */
		$productMock = $this->createMock( WC_Product::class);

		expect('wc_get_product')->with($productId)
		                        ->andReturn($productMock);

		/** @var SubscriptionCrudInterface&MockObject $subscriptionsCrudMock */
		$subscriptionsCrudMock = $this->createConfiguredMock(
			SubscriptionCrudInterface::class,
			['getProductSubscriptionId' => null]
		);

		$sut = new AddToCartValidationEventListener($subscriptionsCrudMock);

		expect('wc_add_notice')->never();

		$addedToCart = $sut->onAddToCart(true, $productId, 2);

		$this->assertSame(true, $addedToCart);
	}
}
