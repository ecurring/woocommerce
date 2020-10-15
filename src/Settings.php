<?php

namespace Ecurring\WooEcurring;

class Settings
{
    public function init()
    {
        add_action(
            'admin_menu',
            function () {
                add_menu_page(
                    'eCurring Settings',
                    'eCurring',
                    'administrator',
                    __FILE__,
                    function () { ?>
                        <div class="wrap">
                            <h1>eCurring Settings</h1>
                            <form method="post" action="options.php">
                                <?php settings_fields( 'ecurring-settings-group' ); ?>
                                <?php do_settings_sections( 'ecurring-settings-group' ); ?>
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">Allow subscription options for customers</th>
                                        <td><fieldset><legend class="screen-reader-text"><span>Allow subscription options for customers</span></legend>
                                                <label for="ecurring_customer_subscription_pause">
                                                    <input name="ecurring_customer_subscription_pause" type="checkbox" value="1"
                                                        <?php checked( get_option('ecurring_customer_subscription_pause'), '1');?>>
                                                    Pause Subscription
                                                </label>
                                                <br>
                                                <label for="ecurring_customer_subscription_switch">
                                                    <input name="ecurring_customer_subscription_switch" type="checkbox" value="1"
                                                        <?php checked( get_option('ecurring_customer_subscription_switch'), '1');?>>
                                                    Switch Subscription
                                                </label>
                                                <br>
                                                <label for="ecurring_customer_subscription_cancel">
                                                    <input name="ecurring_customer_subscription_cancel" type="checkbox" value="1"
                                                        <?php checked( get_option('ecurring_customer_subscription_cancel'), '1');?>>
                                                    Cancel Subscription
                                                </label>
                                            </fieldset></td>
                                    </tr>
                                    </tbody>
                                </table>

                                <?php submit_button(); ?>
                            </form>
                        </div>
                        <?php
                    }
                );
            }
        );

        add_action(
            'admin_init',
            function () {
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
}
