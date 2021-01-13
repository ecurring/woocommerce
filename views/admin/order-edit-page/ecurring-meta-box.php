<?php

declare(strict_types=1);

/**
 * @var $c callable
 * @var $f callable
 */

if (!$c('subscription')) {
    _e('No eCurring subscription found for this order.', 'woo-ecurring');

    return;
}
?>

<h2 style="padding: 8px 0;"><?php esc_html_e('General details', 'woo-ecurring'); ?> </h2>
<p style="padding-left: 15px; line-height: 25px;">

    <?php printf(
    /* translators: %1$s is replaced with the subscription ID. */
        esc_html__('Subscription ID: %1$s', 'woo-ecurring'),
        $c('subscription')->getId()
    ); ?>
    <br>
    <?php
    printf(
    /* translators: %1$s is replaced with the customer ID. */
        esc_html__('Customer ID: %1$s', 'woo-ecurring'),
        $c('customer_id')
    ); ?>
    <br>
</p>

<h2 style="padding: 8px 0;"> <?php _e('Transaction details', 'woo-ecurring'); ?></h2>
<p style="padding-left: 15px; line-height: 25px;">
    <?php if (!$c('transaction_id')) {
        _e('No known transaction yet.', 'woo-ecurring');
        return;
    }

    $transactionIdElement = sprintf(
        '<a href="https://app.ecurring.com/transactions/%1$s" target="_blank">%1$s</a>',
        $c('transaction_id')
    );

    printf(
    /* translators: ID is replaced with clickable transaction ID */
        esc_html__('ID: %1$s', 'woo-ecurring'),
        $transactionIdElement
    );
    ?>


    <?php
    printf(
    /* translators: ID is replaced with transaction status */
        esc_html__('Status: %1$s', 'woo-ecurring'),
        $c('transaction_status') ?? ''
    ); ?>
    <br>

    <br>
    <?php
    printf(
    /* translators: ID is replaced with transaction amount */
        esc_html__('Amount: %1$s', 'woo-ecurring'),
        $c('transaction_amount') ? $f('format_price', $c('transaction_amount')) : ''
    ); ?>
    <br>

    <?php
    printf(
    /* translators: ID is replaced with transaction payment method */
        esc_html__('Method: %1$s', 'woo-ecurring'),
        $c('transaction_payment_method') ?? ''
    ); ?>
    <br>
</p>

