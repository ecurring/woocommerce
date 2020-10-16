<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use Ecurring\WooEcurring\Subscription\SubscriptionCrudInterface;
use eCurring_WC_Plugin;

/**
 * Add to cart validation. Disallow to add to the cart more than one subscription.
 */
class AddToCartValidationEventListener {

	/**
	 * @var SubscriptionCrudInterface
	 */
	protected $subscriptionCrud;

	/**
	 * @param SubscriptionCrudInterface $subscriptionCrud To check if product is subscription product.
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

		if($quantity > 1 || eCurring_WC_Plugin::eCurringSubscriptionIsInCart()) {
			wc_add_notice(
				_x(
					'Please complete your current purchase first before adding another subscription product.',
					'woo-ecurring'
				),
				'error'
			);

			return false;
		}

		return $validationPassed;
	}
}
