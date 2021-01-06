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
        echo wp_kses_post($c('message'));
        ?>
    </div>
    <?php echo $c('select'); ?>
</div>
