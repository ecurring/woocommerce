<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring_WC_Plugin;

class AddToCartValidationEventListener {

	/**
	 * @var SubscriptionCrudInterface
	 */
	protected $subscriptionCrud;

	/**
	 * @param SubscriptionCrudInterface $subscriptionCrud
	 */
	public function __construct(SubscriptionCrudInterface $subscriptionCrud) {

		$this->subscriptionCrud = $subscriptionCrud;
	}

	public function init(): void
	{
		add_filter('woocommerce_add_to_cart_validation', [$this, 'onAddToCart'], 10, 2);
	}

	/**
	 * Prevent adding more than one subscription product to the cart.
	 *
	 * @param bool $validationPassed Validation passed current state.
	 * @param int|string $productId
	 * @param int $quantity
	 *
	 * @return bool Whether add to cart validation passed.
	 */
	public function onAddToCart($validationPassed, $productId, $quantity): bool
	{
		$productToAdd = wc_get_product($productId);

		if($this->subscriptionCrud->getProductSubscriptionId($productToAdd) === null){
			return $validationPassed;
		}

		if($quantity > 1) {
			wc_add_notice(
				_x(
					'You are trying to add more than one subscription product to the cart. Sadly, at the moment it\'s not possible to purchase more than one subscription at once.',
					'Notice on trying to add more than one subscription to the cart',
					'woo-ecurring'
				)
			);

			return false;
		}

		if(! eCurring_WC_Plugin::eCurringSubscriptionIsInCart()){
			return $validationPassed;
		}

		$validationPassed = false;

		wc_add_notice(
			_x(
				'It\s not possible to have more than one subscription product in the cart. Please, purchase or remove from the cart subscription product so you will be able to add to cart another one.',
				'Notice on adding second subscription product to the cart.',
				'woo-ecurring'
			)
		);

		return $validationPassed;
	}
}
