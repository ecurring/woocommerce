<?php

namespace Ecurring\WooEcurring;

use eCurring_WC_Plugin;

class Assets
{
    public function init()
    {
        add_action(
            'admin_enqueue_scripts',
            static function () {
                if (get_current_screen()->id !== 'esubscriptions') {
                    return;
                }

                wp_enqueue_script(
                    'ecurring_admin_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/js/admin-subscriptions.js'),
                    ['jquery'],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/js/admin-subscriptions.js')
                        )
                    )
                );

                wp_enqueue_style(
                    'ecurring_admin_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/css/admin-subscriptions.css'),
                    [],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/css/admin-subscriptions.css')
                        )
                    )
                );
            }
        );

        add_action(
            'wp_enqueue_scripts',
            static function () {
                wp_enqueue_script(
                    'ecurring_customer_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/js/customer-subscriptions.js'),
                    ['jquery'],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/js/customer-subscriptions.js')
                        )
                    )
                );

                wp_localize_script(
                    'ecurring_customer_subscriptions',
                    'ecurring_customer_subscriptions',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                    ]
                );

                wp_enqueue_style(
                    'ecurring_customer_subscriptions',
                    eCurring_WC_Plugin::getPluginUrl('assets/css/customer-subscriptions.css'),
                    [],
                    filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl(
                                'assets/css/customer-subscriptions.css'
                            )
                        )
                    )
                );
            }
        );
    }
}
