<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\Metabox;

use DateTime;
use eCurring_WC_Helper_Api;
use eCurring_WC_Helper_Settings;
use WP_Post;

class Display
{
    public function details(WP_Post $post): void //phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
    {
        $attributes = get_post_meta($post->ID, '_ecurring_post_subscription_attributes', true);
        $subscriptionId = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);
        $relationships = get_post_meta(
            $post->ID,
            '_ecurring_post_subscription_relationships',
            true
        );

        $status = $attributes->status;
        $productId = $relationships->{'subscription-plan'}->data->id;
        $startDate = $attributes->start_date;
        $activatedOn = $attributes->created_at;
        $canceledOn = $attributes->cancel_date;
        $mandateId = $attributes->mandate_code;
        ?>
        <ul>
            <li><?php
                    echo esc_html_x(
                        'Status:',
                        'Admin meta box subscription data',
                        'woo-ecurring'
                    );
                ?> <?php echo esc_attr(ucfirst($status)); ?>
            </li>
            <li><?php
                echo esc_html_x(
                    'Subscription ID:',
                    'Admin meta box subscription data',
                    'woo-ecurring'
                );
                ?> <?php echo esc_attr($subscriptionId);?></li>
            <li><?php
                echo esc_html_x(
                    'Product ID:',
                    'Admin meta box subscription data',
                    'woo-ecurring'
                );
                ?> <?php echo esc_attr($productId);?></li>
            <li><?php
                echo esc_html_x(
                    'Start date:',
                    'Admin meta box subscription data',
                    'woo-ecurring'
                );
                ?> <?php echo esc_attr((new DateTime($startDate))->format('d-m-Y'));?></li>
            <li><?php
                echo esc_html_x(
                    'Activated on:',
                    'Admin meta box subscription data',
                    'woo-ecurring'
                );
                ?> <?php echo esc_attr((new DateTime($activatedOn))->format('d-m-Y H:i:s'));?></li>
            <li><?php
                echo esc_html_x(
                    '(Will be) cancelled on:',
                    'Admin meta box subscription data',
                    'woo-ecurring'
                );
                ?> <?php echo esc_attr((new DateTime($canceledOn))->format('d-m-Y'));?></li>
            <li><?php
                echo esc_html_x(
                    'Mandate ID',
                    'Admin meta box subscription data',
                    'woo-ecurring'
                );
                ?> <?php echo esc_attr($mandateId);?></li>
        </ul>

        <?php
    }

    public function general($post)
    {
        $customer = get_post_meta($post->ID, '_ecurring_post_subscription_customer', true);

        $customerId = $customer->data->id ?? '';
        $firstName = $customer->data->attributes->first_name ?? '';
        $lastName = $customer->data->attributes->last_name ?? '';
        $email = $customer->data->attributes->email ?? '';;
        ?>
        <ul>
            <li>Customer ID: <?php echo esc_attr($customerId);?></li>
            <li>First Name: <?php echo esc_attr($firstName);?></li>
            <li>Last Name: <?php echo esc_attr($lastName);?></li>
            <li>Email: <?php echo esc_attr($email);?></li>
        </ul>
        <?php
    }

    public function options(WP_Post $post): void //phpcs:ignore Inpsyde.CodeQuality.FunctionLength.TooLong
    {
        $subscriptionId = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);
        $attributes = get_post_meta($post->ID, '_ecurring_post_subscription_attributes', true);
        $status = $attributes->status;

        $relationships = get_post_meta(
            $post->ID,
            '_ecurring_post_subscription_relationships',
            true
        );
        $currentProduct = $relationships->{'subscription-plan'}->data->id;

        $settingsHelper = new eCurring_WC_Helper_Settings();
        $api = new eCurring_WC_Helper_Api($settingsHelper);
        $productsResponse = json_decode(
            $api->apiCall('GET', 'https://api.ecurring.com/subscription-plans')
        );

        if(!isset($productsResponse->data)) {
            return;
        }

        $products = [];
        foreach ($productsResponse->data as $product) {
            $products[$product->id] = $product->attributes->name;
        }
        ?>
        <input type="hidden" name="ecurring_subscription_id"
               value="<?php echo esc_attr($subscriptionId) ?>">
        <select name="ecurring_subscription" id="ecurring_subscription_options">
            <option value=""
            ><?php
                echo esc_html_x(
                    'Select an option',
                    'Admin meta box option name',
                    'woo-ecurring'
                ); ?>
            </option><?php if ($status === 'paused') { ?>
                <option value="resume"
                ><?php
                   echo esc_html_x(
                       'Resume subscription',
                       'Admin meta box option name',
                       'woo-ecurring'
                   ); ?></option>
                     <?php } else { ?>
                <option value="pause"
                ><?php
                    echo esc_html_x(
                        'Pause subscription',
                        'Admin meta box option name',
                        'woo-ecurring'
                    ); ?></option>
                <option value="switch"
                ><?php
                    echo esc_html_x(
                        'Switch subscription',
                        'Admin meta box option name',
                        'woo-ecurring'
                    ); ?></option>
                     <?php } ?>
            <option value="cancel"
            ><?php
                echo esc_html_x(
                    'Cancel subscription',
                    'Admin meta box option name',
                    'woo-ecurring'
                ); ?></option>
        </select>
        <div class="ecurring-hide" id="pause-form">
            <h3><?php
                echo esc_html_x(
                    'Pause subscription',
                    'Admin meta box content',
                    'woo-ecurring'
                ); ?> - <?php echo esc_attr($subscriptionId); ?></h3>
            <p><?php
                echo esc_html_x(
                    'Please select until when this subscription should be paused.',
                    'Admin meta box content',
                    'woo-ecurring'
                ); ?></p>
            <h4><?php
                echo esc_html_x(
                    'Pause until',
                    'Admin meta box content',
                    'woo-ecurring'
                ); ?></h4>
            <label><input name="ecurring_pause_subscription" type="radio" value="infinite"
                          class="tog"
                          checked="checked"/><?php
                                                    echo esc_html_x(
                                                        'Infinite',
                                                        'Admin meta box context',
                                                        'woo-ecurring'
                                                    );?></label>
            <label><input name="ecurring_pause_subscription" type="radio" value="specific-date"
                          class="tog"
                /><?php
                        echo esc_html_x(
                            'Specific date',
                            'Admin meta box label (pause subscription until)',
                            'woo-ecurring'
                        );
                    ?></label>
            <input name="ecurring_resume_date" type="date"
                   value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
        </div>
        <div class="ecurring-hide" id="switch-form">
            <h3><?php
                echo esc_html_x(
                    'Switch subscription',
                    'Admin meta box label (pause subscription until)',
                    'woo-ecurring'
                );
                ?> - <?php echo esc_attr($subscriptionId); ?></h3>
            <p><?php
                echo esc_html_x(
                    'This form allows you to automatically switch a subscription to a different 
                    plan on a desired date. The current mandate will be used for the 
                    new subscription, no confirmation from the client is necessary.',
                    'Admin meta box form description',
                    'woo-ecurring'
                ); ?>
            </p>
            <p><?php
                esc_html_e(
                    'Current product:',
                    'woo-ecurring'
                ); ?> <?php echo esc_attr($products[$currentProduct]); ?></p>
            <select id="ecurring_subscription_plan" name="ecurring_subscription_plan">
                <?php foreach ($products as $key => $value) { ?>
                    <option value="<?php echo esc_attr($key); ?>"
                        <?php selected($currentProduct, $key);?>
                    ><?php echo esc_attr($value); ?></option>
                <?php }; ?>
            </select>
            <p><?php
                echo esc_html_x(
                    'Indicate the date on which the new subscription should start. The current
                          subscription will automatically be terminated on this date',
                    'Admin meta box content',
                    'woo-ecurring'
                ); ?>
                </p>
            <h4><?php
                echo esc_html_x(
                    'Switch on',
                    'Admin meta box label (pause subscription until)',
                    'woo-ecurring'
                ); ?></h4>
            <label><input
                        name="ecurring_switch_subscription"
                        type="radio"
                        value="immediately"
                        class="tog"
                        checked="checked"
                /><?php
                            echo esc_html_x(
                                'Immediately',
                                'Admin meta box label text',
                                'woo-ecurring'
                            );
                    ?></label>
            <label><input name="ecurring_switch_subscription" type="radio" value="specific-date"
                          class="tog"/><?php
                                        echo esc_html_x(
                                            'Specific date',
                                            'Admin meta box label text',
                                            'woo-ecurring'
                                        );
                                        ?></label>
            <input name="ecurring_switch_date" type="date"
                   value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
        </div>
        <div class="ecurring-hide" id="cancel-form">
            <h3><?php
                echo esc_html_x(
                    'Cancel subscription',
                    'Admin meta box content',
                    'woo-ecurring'
                );
                ?> - <?php echo esc_attr($subscriptionId); ?></h3>
            <p><?php
                echo esc_html_x(
                    'Please choose when to cancel the subscription.',
                    'Admin meta box content',
                    'woo-ecurring'
                ); ?></p>
            <h4><?php echo esc_html_x('Cancel on', '', 'woo-ecurring'); ?></h4>
            <label><input
                        name="ecurring_cancel_subscription"
                        type="radio"
                        value="infinite"
                        class="tog"
                        checked="checked"
                /><?php esc_html_x('Infinite', 'Admin meta box content', 'woo-ecurring');?></label>
            <label><input name="ecurring_cancel_subscription" type="radio" value="specific-date"
                          class="tog"/><?php esc_html_x('Specific date', 'Admin meta box content', 'woo-ecurring'); ?></label>
            <input name="ecurring_cancel_date" type="date"
                   value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
        </div>
    <?php }
}
