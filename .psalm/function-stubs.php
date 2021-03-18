<?php

//phpcs: Ignore

function in_the_loop(): bool
{
    return false;
}

function get_user_meta($userId, $key = '', $single = false)
{
}

function update_user_meta(int $userId, string $metaKey, string $previous = '')
{
}

function delete_user_meta(int $userId, string $metaKey, string $metaValue = '')
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

function wc_get_page_permalink($page, $fallback = null)
{
    return '';
}

function wc_price($value): string
{
    return (string) $value;
}

function wp_post_revision_title( $revision, $link = true )
{
    return '';
}

function get_query_var($var, $default = '')
{
    return '';
}

function wc_get_endpoint_url($endpoint, $var = '')
{
    return '';
}
