<?php

namespace Ecurring\WooEcurring\Customer;

use DateTime;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Api\SubscriptionPlans;

class Subscriptions
{
    /**
     * @var Customers
     */
    private $customer;

    /**
     * @var SubscriptionPlans
     */
    private $subscriptionPlans;

    public function __construct(Customers $customer, SubscriptionPlans $subscriptionPlans)
    {
        $this->customer = $customer;
        $this->subscriptionPlans = $subscriptionPlans;
    }

    public function display()
    {
        $customerId = get_user_meta(get_current_user_id(), 'ecurring_customer_id', true);
        $subscriptions = $this->customer->getCustomerSubscriptions($customerId);
        $subscriptionsData = $subscriptions->data ?? [];

        $subscriptionPlans = $this->subscriptionPlans->getSubscriptionPlans();
        $subscriptionPlansData = $subscriptionPlans->data ?? [];
        $products = [];
        foreach ($subscriptionPlansData as $product) {
            $products[$product->id] = $product->attributes->name;
        }
        ?>

        <table class="woocommerce-orders-table shop_table shop_table_responsive">
            <thead>
            <tr>
                <th class="woocommerce-orders-table__header">Subscription</th>
                <th class="woocommerce-orders-table__header">Product</th>
                <th class="woocommerce-orders-table__header">Status</th>
                <?php if ($this->allowAtLeastOneOption()) { ?>
                    <th class="woocommerce-orders-table__header">Options</th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($subscriptionsData as $subscription) {
                if (!$subscription) {
                    continue;
                }
                ?>
                <tr class="woocommerce-orders-table__row order">
                    <td class="woocommerce-orders-table__cell" data-title="Subscription">
                        <?php echo esc_attr($subscription->id); ?>
                    </td>
                    <td class="woocommerce-orders-table__cell" data-title="Product">
                        <?php echo esc_attr(
                            $products[$subscription->relationships->{'subscription-plan'}->data->id]
                        ); ?>
                    </td>
                    <td class="woocommerce-orders-table__cell" data-title="Status">
                        <?php echo esc_attr(ucfirst($subscription->attributes->status)); ?>
                    </td>
                    <?php if ($this->allowAtLeastOneOption()) { ?>
                        <td class="woocommerce-orders-table__cell" data-title="Options">
                            <form class="subscription-options"
                                  data-subscription="<?php echo esc_attr($subscription->id); ?>">
                                <select style="width:100%;" name="ecurring_subscription"
                                        class="ecurring_subscription_options"
                                        data-subscription="<?php echo esc_attr(
                                            $subscription->id
                                        ); ?>">
                                    <option value="">Select an option</option>
                                    <?php if ($subscription->attributes->status === 'paused') { ?>
                                        <?php if($this->allowOption('pause')) { ?>
                                            <option value="resume">Resume subscription</option>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if($this->allowOption('pause')) { ?>
                                            <option value="pause">Pause subscription</option>
                                        <?php } ?>
                                        <?php if($this->allowOption('switch')) { ?>
                                            <option value="switch">Switch subscription</option>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if($this->allowOption('cancel')) { ?>
                                        <option value="cancel">Cancel subscription</option>
                                    <?php } ?>
                                </select>

                                <?php if($this->allowOption('pause')) { ?>
                                    <div class="ecurring-hide pause-form"
                                         data-subscription="<?php echo esc_attr($subscription->id); ?>">
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
                                <?php } ?>
                                <?php if($this->allowOption('switch')) { ?>
                                    <div class="ecurring-hide switch-form"
                                         data-subscription="<?php echo esc_attr($subscription->id); ?>">
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
                                <?php } ?>
                                <?php if($this->allowOption('cancel')) { ?>
                                    <div class="ecurring-hide cancel-form"
                                         data-subscription="<?php echo esc_attr($subscription->id); ?>">
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
                                <?php } ?>
                            </form>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php }


    protected function allowAtLeastOneOption(): bool
    {
        $allowPause = get_option('ecurring_customer_subscription_pause');
        $allowSwitch = get_option('ecurring_customer_subscription_switch');
        $allowCancel = get_option('ecurring_customer_subscription_cancel');

        if ($allowPause === '1' || $allowSwitch === '1' || $allowCancel === '1') {
            return true;
        }

        return false;
    }

    protected function allowOption(string $option): bool
    {
        $option = get_option("ecurring_customer_subscription_{$option}");
        if ($option === '1') {
            return true;
        }

        return false;
    }
}
