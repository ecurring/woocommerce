<?php

//phpcs: Ignore

function in_the_loop(): bool
{
    return false;
}

function get_user_meta($userId, $key = '', $single = false)
{
}

function get_current_user_id(): int
{
    return 0;
}

function wc_get_product($productId = false): WC_Product
{
    return new WC_Product();
}

function wc_add_notice($message, $noticeType = 'success', $data = []): void
{
}

function _x(string $text, string $context, string $textDomain): string
{
    return $text;
}
