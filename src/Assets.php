<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use eCurring_WC_Plugin;
use WP_Screen;

class Assets
{
    /**
     * @var string
     */
    protected $pluginAssetsPath = '';

    public function init(): void
    {
        $this->enqueueAdminScripts();
        $this->enqueueFrontScripts();

        $this->pluginAssetsPath = plugin_dir_path(WOOECUR_PLUGIN_FILE) . 'assets/';
    }

    protected function enqueueAdminScripts(): void
    {
        add_action(
            'admin_enqueue_scripts',
            function () {
                $screen = get_current_screen();
                if ($screen instanceof WP_Screen && $screen->id !== 'esubscriptions') {
                    return;
                }

                $scriptFileUrl = eCurring_WC_Plugin::getPluginUrl('assets/js/admin-subscriptions.js');

                wp_enqueue_script(
                    'ecurring_admin_subscriptions',
                    $scriptFileUrl,
                    ['jquery'],
                    (string) filemtime($this->pluginAssetsPath . 'js/admin-subscriptions.js')
                );

                $stylesFileUrl = eCurring_WC_Plugin::getPluginUrl('assets/css/admin-subscriptions.css');

                wp_enqueue_style(
                    'ecurring_admin_subscriptions',
                    $stylesFileUrl,
                    [],
                    (string) filemtime($this->pluginAssetsPath . 'css/admin-subscriptions.css')
                );
            }
        );
    }

    protected function enqueueFrontScripts(): void
    {
        add_action(
            'wp_enqueue_scripts',
            function () {
                $scriptFileUrl = eCurring_WC_Plugin::getPluginUrl('assets/js/customer-subscriptions.js');
                wp_enqueue_script(
                    'ecurring_customer_subscriptions',
                    $scriptFileUrl,
                    ['jquery'],
                    (string) filemtime($scriptFilePath)
                );

                wp_localize_script(
                    'ecurring_customer_subscriptions',
                    'ecurring_customer_subscriptions',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                    ]
                );

                $stylesFileUrl = eCurring_WC_Plugin::getPluginUrl('assets/css/customer-subscriptions.css');

                wp_enqueue_style(
                    'ecurring_customer_subscriptions',
                    $stylesFileUrl,
                    [],
                    (string) filemtime($stylesFilePath)
                );
            }
        );
    }
}
