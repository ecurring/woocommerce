<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

class Settings
{
    public function init(): void
    {
        add_action(
            'admin_menu',
            function () {
                add_menu_page(
                    'Settings',
                    'eCurring',
                    'administrator',
                    __FILE__,
                    function () {
                        echo $this->renderSettingsPage(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                );
            }
        );

        add_action(
            'admin_init',
            static function () {
                register_setting('ecurring-settings-group', 'ecurring_customer_subscription_pause');
                register_setting(
                    'ecurring-settings-group',
                    'ecurring_customer_subscription_switch'
                );
                register_setting(
                    'ecurring-settings-group',
                    'ecurring_customer_subscription_cancel'
                );
            }
        );
    }

    /**
     * Produce the Settings page HTML.
     *
     * @return string
     */
    protected function renderSettingsPage(): string //phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
    {
        ob_start(); ?>
        <div class="wrap">
            <h1><?php
                echo esc_html_x(
                    'Mollie Subscriptions Settings',
                    'Settings page title',
                    'woo-ecurring'
                );
                ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('ecurring-settings-group'); ?>
                <?php do_settings_sections('ecurring-settings-group'); ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><?php
                            esc_html_e('Allow subscription options for customers', 'woo-ecurring');
                        ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span
                                    ><?php
                                        esc_html_e(
                                            'Allow subscription options for customers',
                                            'woo-ecurring'
                                        ); ?></span>
                                </legend>
                                <label for="ecurring_customer_subscription_pause">
                                    <input name="ecurring_customer_subscription_pause" type="checkbox" value="1"
                                        <?php checked(get_option('ecurring_customer_subscription_pause'), '1'); ?>>
                                    <?php esc_html_e('Pause Subscription', 'woo-ecurring'); ?>
                                </label>
                                <br>
                                <label for="ecurring_customer_subscription_switch">
                                    <input name="ecurring_customer_subscription_switch" type="checkbox" value="1"
                                        <?php checked(get_option('ecurring_customer_subscription_switch'), '1'); ?>>
                                    <?php esc_html_e('Switch Subscription', 'woo-ecurring'); ?>
                                </label>
                                <br>
                                <label for="ecurring_customer_subscription_cancel">
                                    <input name="ecurring_customer_subscription_cancel" type="checkbox" value="1"
                                        <?php checked(get_option('ecurring_customer_subscription_cancel'), '1'); ?>>
                                    <?php esc_html_e('Cancel Subscription', 'woo-ecurring'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
