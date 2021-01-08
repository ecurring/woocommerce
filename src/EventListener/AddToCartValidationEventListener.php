<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

use eCurring_WC_Plugin;

use function add_filter;
use function wc_get_product;
use function wc_add_notice;
use function _x;

/**
 * Add to cart validation. Disallow to add to the cart more than one subscription.
 */
class AddToCartValidationEventListener implements EventListenerInterface
{

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

        if ($productToAdd->get_meta('_ecurring_subscription_plan')) {
            return $validationPassed;
        }

        if (get_current_user_id() === 0) {
            $this->addLoginNeededNotice();

            return false;
        }

        if ($quantity > 1 || eCurring_WC_Plugin::eCurringSubscriptionIsInCart()) {
            wc_add_notice(
                _x(
                    'Please complete your current purchase first before adding another subscription product.',
                    'User notice when trying to add more than one subscription product to the cart',
                    'woo-ecurring'
                ),
                'error'
            );

            return false;
        }

        return $validationPassed;
    }

    /**
     * Add notice for the guest customer about login is requered to buy subscription.
     */
    protected function addLoginNeededNotice(): void
    {
        $loginPageLinkOpeningTag = sprintf(
            '<a href="%1$s">',
            wc_get_page_permalink('myaccount')
        );

        $loginPageLinkClosingTag = '</a>';

        $loginNeededMessage = sprintf(
            /* translators: %1$s is replaced with opening html <a> tag, %2$s is replaced with the closing html </a> tag */
            _x(
                'Please, %1$slogin or register%2$s first to be able to purchase subscription.',
                'User notice when guest customer tries to purchase subscription',
                'woo-ecurring'
            ),
            $loginPageLinkOpeningTag,
            $loginPageLinkClosingTag
        );

        wc_add_notice(
            wp_kses_post(sprintf(
                '<p>%1$s</p>',
                $loginNeededMessage
            )),
            'error'
        );
    }
}
