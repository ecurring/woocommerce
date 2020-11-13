<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use eCurring_WC_Plugin;
use WP_Screen;

class Assets
{
    public function init(): void
    {
        $this->enqueueAdminScripts();
        $this->enqueueFrontScripts();
    }

    protected function enqueueAdminScripts(): void
    {
        add_action(
            'admin_enqueue_scripts',
            static function () {
                $screen = get_current_screen();
                if ($screen instanceof WP_Screen && $screen->id !== 'esubscriptions') {
                    return;
                }

                $scriptFilePath = eCurring_WC_Plugin::getPluginUrl('assets/js/admin-subscriptions.js');

                wp_enqueue_script(
                    'ecurring_admin_subscriptions',
                    $scriptFilePath,
                    ['jquery'],
                    (string) filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/js/admin-subscriptions.js')
                        )
                    )
                );

                $stylesFilePath = eCurring_WC_Plugin::getPluginUrl('assets/css/admin-subscriptions.css');

                wp_enqueue_style(
                    'ecurring_admin_subscriptions',
                    $stylesFilePath,
                    [],
                    (string) filemtime(
                        get_template_directory(
                            eCurring_WC_Plugin::getPluginUrl('assets/css/admin-subscriptions.css')
                        )
                    )
                );
            }
        );
    }

    protected function enqueueFrontScripts(): void
    {
        add_action(
            'wp_enqueue_scripts',
            static function () {
                $scriptFilePath = eCurring_WC_Plugin::getPluginUrl('assets/js/customer-subscriptions.js');
                wp_enqueue_script(
                    'ecurring_customer_subscriptions',
                    $scriptFilePath,
                    ['jquery'],
                    (string) filemtime(
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

                $stylesFilePath = eCurring_WC_Plugin::getPluginUrl('assets/css/customer-subscriptions.css');

                wp_enqueue_style(
                    'ecurring_customer_subscriptions',
                    $stylesFilePath,
                    [],
                    (string) filemtime(
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
