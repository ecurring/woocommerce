<?php

namespace Ecurring\WooEcurring\Subscription\Metabox;

use DateTime;
use eCurring_WC_Helper_Api;
use eCurring_WC_Helper_Settings;
use WP_Post;

class Display
{
    public function details(WP_Post $post): void
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
            <li>Status: <?php echo esc_attr(ucfirst($status)); ?></li>
            <li>Subscription ID: <?php echo esc_attr($subscriptionId);?></li>
            <li>Product ID: <?php echo esc_attr($productId);?></li>
            <li>Start date: <?php echo esc_attr((new DateTime($startDate))->format('d-m-Y'));?></li>
            <li>Activated on: <?php echo esc_attr((new DateTime($activatedOn))->format('d-m-Y H:i:s'));?></li>
            <li>(Will be) cancelled on: <?php echo esc_attr((new DateTime($canceledOn))->format('d-m-Y'));?></li>
            <li>Mandate ID: <?php echo esc_attr($mandateId);?></li>
        </ul>

        <?php
    }

    public function options(WP_Post $post): void
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
        $products = [];
        foreach ($productsResponse->data as $product) {
            $products[$product->id] = $product->attributes->name;
        }
        ?>
        <input type="hidden" name="ecurring_subscription_id"
               value="<?php echo esc_attr($subscriptionId) ?>">
        <select name="ecurring_subscription" id="ecurring_subscription_options">
            <option value="">Select an option</option>
            <?php if ($status === 'paused') { ?>
                <option value="resume">Resume subscription</option>
            <?php } else { ?>
                <option value="pause">Pause subscription</option>
                <option value="switch">Switch subscription</option>
            <?php } ?>
            <option value="cancel">Cancel subscription</option>
        </select>
        <div class="ecurring-hide" id="pause-form">
            <h3>Pause subscription - <?php echo esc_attr($subscriptionId); ?></h3>
            <p>Please select until when this subscription should be paused.</p>
            <h4>Pause until</h4>
            <label><input name="ecurring_pause_subscription" type="radio" value="infinite"
                          class="tog"
                          checked="checked"/>Infinite</label>
            <label><input name="ecurring_pause_subscription" type="radio" value="specific-date"
                          class="tog"/>Specific
                date</label>
            <input name="ecurring_resume_date" type="date"
                   value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
        </div>
        <div class="ecurring-hide" id="switch-form">
            <h3>Switch subscription - <?php echo esc_attr($subscriptionId); ?></h3>
            <p>This form allows you to automatically switch a subscription to a different plan on a
                desired date. The current mandate will be used for the new subscription, no
                confirmation from the client is necessary.
            </p>
            <p>Current product: <?php echo esc_attr($products[$currentProduct]); ?></p>
            <select id="ecurring_subscription_plan" name="ecurring_subscription_plan">
                <?php foreach ($products as $key => $value) { ?>
                    <option value="<?php echo esc_attr($key); ?>"
                        <?php selected($currentProduct, $key);?>
                    ><?php echo esc_attr($value); ?></option>
                <?php }; ?>
            </select>
            <p>Indicate the date on which the new subscription should start. The current
                subscription will automatically be terminated on this date</p>
            <h4>Switch on</h4>
            <label><input name="ecurring_switch_subscription" type="radio" value="immediately" class="tog"
                          checked="checked"/>Immediately</label>
            <label><input name="ecurring_switch_subscription" type="radio" value="specific-date"
                          class="tog"/>Specific date</label>
            <input name="ecurring_switch_date" type="date"
                   value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
        </div>
        <div class="ecurring-hide" id="cancel-form">
            <h3>Cancel subscription - <?php echo esc_attr($subscriptionId);?></h3>
            <p>Please choose when to cancel the subscription.</p>
            <h4>Cancel on</h4>
            <label><input name="ecurring_cancel_subscription" type="radio" value="infinite" class="tog"
                          checked="checked"/>Infinite</label>
            <label><input name="ecurring_cancel_subscription" type="radio" value="specific-date"
                          class="tog"/>Specific date</label>
            <input name="ecurring_cancel_date" type="date"
                   value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
        </div>
    <?php }
}
