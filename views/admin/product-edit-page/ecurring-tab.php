<?php

declare(strict_types=1);

/**
 * @var $c callable
 * @var $f callable
 */
?>
<div id="woo_ecurring_product_data" class="panel woocommerce_options_panel">

    <div style="padding: 15px;">
        <?php
        $message = __(
            'You are adding an eCurring product. The eCurring product determines the price your customers will pay when purchasing this product. Make sure the product price in WooCommerce exactly matches the eCurring product price. Important: the eCurring product determines the price your customers will pay when purchasing this product. Make sure the product price in WooCommerce exactly matches the eCurring product price. The eCurring product price should include all shipping cost. Any additional shipping costs added by WooCommerce will not be charged.',
            'woo-ecurring'
        );
        echo wp_kses_post($message);
        ?>
    </div>
    <?php echo $c('select'); ?>
</div>
