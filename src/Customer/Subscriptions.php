<?php

namespace Ecurring\WooEcurring\Customer;

use DateTime;
use eCurring_WC_Helper_Api;

class Subscriptions
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    private $api;

    public function __construct(eCurring_WC_Helper_Api $api)
    {
        $this->api = $api;
    }

    public function display()
    {
        $customerId = get_user_meta(get_current_user_id(), 'ecurring_customer_id', true);
        $subscriptions = json_decode(
            $this->api->apiCall(
                'GET',
                "https://api.ecurring.com/customers/{$customerId}/subscriptions"
            )
        );

        $productsResponse = json_decode(
            $this->api->apiCall('GET', 'https://api.ecurring.com/subscription-plans')
        );
        $products = [];
        foreach ($productsResponse->data as $product) {
            $products[$product->id] = $product->attributes->name;
        }
        ?>

        <table class="woocommerce-orders-table shop_table shop_table_responsive">
            <thead>
            <tr>
                <th class="woocommerce-orders-table__header">Subscription</th>
                <th class="woocommerce-orders-table__header">Product</th>
                <th class="woocommerce-orders-table__header">Status</th>
                <th class="woocommerce-orders-table__header">Options</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($subscriptions->data as $subscription) { ?>
                <tr class="woocommerce-orders-table__row order">
                    <td class="woocommerce-orders-table__cell" data-title="Subscription">
                        <?php echo $subscription->id; ?>
                    </td>
                    <td class="woocommerce-orders-table__cell" data-title="Product">
                        <?php echo $products[$subscription->relationships->{'subscription-plan'}->data->id]; ?>
                    </td>
                    <td class="woocommerce-orders-table__cell" data-title="Status">
                        <?php echo ucfirst($subscription->attributes->status); ?>
                    </td>
                    <td class="woocommerce-orders-table__cell" data-title="Options">
                        <form class="subscription-options" data-subscription="<?php echo $subscription->id; ?>">
                            <select style="width:100%;" name="ecurring_subscription"
                                    class="ecurring_subscription_options"
                                    data-subscription="<?php echo $subscription->id; ?>">
                                <option value="">Select an option</option>
                                <?php if ($subscription->attributes->status === 'paused') { ?>
                                    <option value="resume">Resume subscription</option>
                                <?php } else { ?>
                                    <option value="pause">Pause subscription</option>
                                    <option value="switch">Switch subscription</option>
                                <?php } ?>
                                <option value="cancel">Cancel subscription</option>
                            </select>
                            <div class="ecurring-hide pause-form"
                                 data-subscription="<?php echo $subscription->id; ?>">
                                <label><input name="ecurring_pause_subscription" type="radio"
                                              value="infinite"
                                              class="tog"
                                              checked="checked"/>Infinite</label>
                                <label><input name="ecurring_pause_subscription" type="radio"
                                              value="specific-date"
                                              class="tog"/>Specific
                                    date</label>
                                <input class="ecurring-hide" name="ecurring_resume_date" type="date"
                                       value="<?php echo esc_attr(
                                           (new DateTime('now'))->format('Y-m-d')
                                       ); ?>">
                                <button>Update</button>
                            </div>
                            <button class="resume-update ecurring-hide">Update</button>
                            <div class="ecurring-hide switch-form"
                                 data-subscription="<?php echo $subscription->id; ?>">
                                <select class="ecurring_subscription_plan"
                                        name="ecurring_subscription_plan">
                                    <?php foreach ($products as $key => $value) { ?>
                                        <option value="<?php echo esc_attr($key); ?>"
                                            <?php selected(
                                                $subscription->relationships->{'subscription-plan'}->data->id,
                                                $key
                                            ); ?>
                                        ><?php echo esc_attr($value); ?></option>
                                    <?php }; ?>
                                </select>
                                <label><input name="ecurring_switch_subscription" type="radio"
                                              value="immediately" class="tog"
                                              checked="checked"/>Immediately</label>
                                <label><input name="ecurring_switch_subscription" type="radio"
                                              value="specific-date"
                                              class="tog"/>Specific date</label>
                                <input name="ecurring_switch_date" type="date"
                                       value="<?php echo esc_attr(
                                           (new DateTime('now'))->format('Y-m-d')
                                       ); ?>">
                                <button>Update</button>
                            </div>
                            <div class="ecurring-hide cancel-form"
                                 data-subscription="<?php echo $subscription->id; ?>">
                                <label><input name="ecurring_cancel_subscription" type="radio"
                                              value="infinite" class="tog"
                                              checked="checked"/>Infinite</label>
                                <label><input name="ecurring_cancel_subscription" type="radio"
                                              value="specific-date"
                                              class="tog"/>Specific date</label>
                                <input name="ecurring_cancel_date" type="date"
                                       value="<?php echo esc_attr(
                                           (new DateTime('now'))->format('Y-m-d')
                                       ); ?>">
                                <button>Update</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php }
}
