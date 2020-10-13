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
		add_filter('woocommerce_add_to_cart_validation', [$this, 'onAddToCart'], 10, 3);
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

		$errorMessage = _x(
			'Please complete your current purchase first before adding another subscription product.',
			'woo-ecurring'
		);

		if($quantity > 1) {
			wc_add_notice(
				$errorMessage,
				'error'
			);

			return false;
		}

		if(! eCurring_WC_Plugin::eCurringSubscriptionIsInCart()){
			return $validationPassed;
		}

		wc_add_notice(
			$errorMessage,
			'error'
		);

		return false;
	}
}
