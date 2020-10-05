<?php

namespace eCurring\WooEcurring\Subscription\Metabox;

class Display
{
    public function details($post)
    {
        $attributes = get_post_meta(
            $post->ID,
            '_ecurring_post_subscription_attributes',
            true
        );

        echo esc_attr(ucfirst($attributes->status));
    }

    public function options($post)
    {
        $subscriptionId = get_post_meta($post->ID, '_ecurring_post_subscription_id', true);
        ?>
        <input type="hidden" name="ecurring_subscription_id"
               value="<?php echo esc_attr($subscriptionId) ?>">
        <select name="ecurring_subscription" id="ecurring_subscription_options">
            <option value="">Select an option</option>
            <option value="pause">Pause subscription</option>
            <option value="switch">Switch subscription</option>
            <option value="cancel">Cancel subscription</option>
        </select>
        <div class="ecurring-hide" id="pause-form">
            <h3>Pause subscription - 2125431460</h3>
            <p>Please select until when this subscription should be paused.</p>
            <h4>Pause until</h4>
            <label><input name="pause_subscription" type="radio" value="infinite" class="tog"
                          checked="checked"/>Infinite</label>
            <label><input name="pause_subscription" type="radio" value="specific-date" class="tog"/>Specific
                date</label>
        </div>
        <div class="ecurring-hide" id="switch-form">
            <h3>Switch subscription - 2125431460</h3>
            <p>This form allows you to automatically switch a subscription to a different plan on a
                desired date. The current mandate will be used for the new subscription, no
                confirmation from the client is necessary.
            </p>
            <p>Current product: Test Product From Testing</p>
            <select id="ecurring_subscription_plans">
                <option value="123">Test Product From Testing</option>
                <option value="456">Another Product</option>
                <option value="780">Just another</option>
            </select>
            <p>Indicate the date on which the new subscription should start. The current
                subscription will automatically be terminated on this date</p>
            <h4>Switch on</h4>
            <label><input name="switch_subscription" type="radio" value="infinite" class="tog"
                          checked="checked"/>Infinite</label>
            <label><input name="switch_subscription" type="radio" value="specific-date"
                          class="tog"/>Specific date</label>
        </div>
        <div class="ecurring-hide" id="cancel-form">
            <h3>Cancel subscription - 2125431460</h3>
            <p>Please choose when to cancel the subscription.</p>
            <h4>Cancel on</h4>
            <label><input name="cancel_subscription" type="radio" value="infinite" class="tog"
                          checked="checked"/>Infinite</label>
            <label><input name="cancel_subscription" type="radio" value="specific-date"
                          class="tog"/>Specific date</label>
        </div>
    <?php }
}
