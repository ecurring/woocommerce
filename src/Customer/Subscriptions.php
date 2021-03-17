<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use DateTime;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Api\SubscriptionPlans;
use Ecurring\WooEcurring\Settings\SettingsCrudInterface;
use Ecurring\WooEcurring\Subscription\Repository;

use function get_user_meta;
use function get_current_user_id;
use function esc_attr;
use function ucfirst;
use function selected;

class Subscriptions
{
    /**
     * @var SettingsCrudInterface
     */
    protected $settingsCrud;
    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var Customers
     */
    private $customer;

    /**
     * @var SubscriptionPlans
     */
    private $subscriptionPlans;

    /**
     * @param Customers $customer Customers API client.
     * @param SubscriptionPlans $subscriptionPlans Subscription plans API client.
     * @param SettingsCrudInterface $settingsCrud Settings storage.
     * @param Repository $repository Subscriptions repository.
     */
    public function __construct(
        Customers $customer,
        SubscriptionPlans $subscriptionPlans,
        SettingsCrudInterface $settingsCrud,
        Repository $repository
    ) {
        $this->customer = $customer;
        $this->subscriptionPlans = $subscriptionPlans;
        $this->settingsCrud = $settingsCrud;
        $this->repository = $repository;
    }

    //phpcs:ignore Inpsyde.CodeQuality.FunctionLength.TooLong
    public function display(): void
    {
        $customerId = get_user_meta(get_current_user_id(), 'ecurring_customer_id', true);
        $subscriptionsList = $this->repository->getSubscriptionsByEcurringCustomerId($customerId);

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
                <th class="woocommerce-orders-table__header"
                ><?php
                 echo esc_html_x(
                     'Subscription',
                     'Column name of the table on Subscriptions page in My account',
                     'woo-ecurring'
                 ); ?>
                </th>
                <th class="woocommerce-orders-table__header"
                ><?php
                    echo esc_html_x(
                        'Product',
                        'Column name of the table on Subscriptions page in My account',
                        'woo-ecurring'
                    ); ?>
                </th>
                <th class="woocommerce-orders-table__header"
                ><?php
                    echo esc_html_x(
                        'Status',
                        'Column name of the table on Subscriptions page in My account',
                        'woo-ecurring'
                    ); ?>
                </th>
                <?php if ($this->allowAtLeastOneOption()) { ?>
                    <th class="woocommerce-orders-table__header"
                    ><?php
                        echo esc_html_x(
                            'Options',
                            'Column name of the table on Subscriptions page in My account',
                            'woo-ecurring'
                        ); ?>
                    </th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($subscriptionsList as $subscription) { ?>
                <tr class="woocommerce-orders-table__row order">
                    <td class="woocommerce-orders-table__cell" data-title="Subscription">
                        <?php echo esc_attr($subscription->getId()); ?>
                    </td>
                    <td class="woocommerce-orders-table__cell" data-title="Product">
                        <?php echo esc_attr(
                            $products[$subscription->getSubscriptionPlanId()]
                        ); ?>
                    </td>
                    <td class="woocommerce-orders-table__cell" data-title="Status">
                        <?php echo esc_attr(
                            ucfirst($subscription->getStatus()->getCurrentStatus())
                        ); ?>
                    </td>
                    <?php if ($this->allowAtLeastOneOption()) { ?>
                        <td class="woocommerce-orders-table__cell" data-title="Options">
                            <form class="subscription-options"
                                  data-subscription="<?php echo esc_attr($subscription->getId()); ?>">
                                <select style="width:100%;" name="ecurring_subscription"
                                        class="ecurring_subscription_options"
                                        data-subscription="<?php
                                        echo esc_attr($subscription->getId()); ?>"
                                >
                                    <option value=""><?php
                                                        echo esc_html_x(
                                                            'Select an option',
                                                            'Option name on My Account page',
                                                            'woo-ecurring'
                                                        );
                                                        ?></option>
                                    <?php if ( $subscription->getStatus()->getCurrentStatus() === 'paused') { ?>
                                        <?php if ($this->allowOption('pause')) { ?>
                                            <option value="resume"
                                            ><?php
                                                echo esc_html_x(
                                                    'Resume subscription',
                                                    'Option name on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                );
                                                ?></option>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($this->allowOption('pause')) { ?>
                                            <option value="pause"
                                            ><?php
                                                echo esc_html_x(
                                                    'Pause subscription',
                                                    'Option name on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                );
                                                ?></option>
                                        <?php } ?>
                                        <?php if ($this->allowOption('switch')) { ?>
                                            <option value="switch"
                                            ><?php
                                                echo esc_html_x(
                                                    'Switch subscription',
                                                    'Option name on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                );
                                                ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if ($this->allowOption('cancel')) { ?>
                                        <option value="cancel"
                                        ><?php
                                            echo esc_html_x(
                                                'Cancel subscription',
                                                'Option name on the Subscriptions page in my account',
                                                'woo-ecurring'
                                            );
                                            ?></option>
                                    <?php } ?>
                                </select>

                                <?php if ($this->allowOption('pause')) { ?>
                                    <div class="ecurring-hide pause-form"
                                         data-subscription="<?php echo esc_attr($subscription->getId()); ?>">
                                        <label><input name="ecurring_pause_subscription"
                                                      type="radio"
                                                      value="infinite"
                                                      class="tog"
                                                      checked="checked"
                                            /><?php
                                                echo esc_html_x(
                                                    'Infinite',
                                                    'Label on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                ); ?>
                                        </label>
                                        <label><input name="ecurring_pause_subscription"
                                                      type="radio"
                                                      value="specific-date"
                                                      class="tog"/>
                                            <?php
                                                echo esc_html_x(
                                                    'Specific date',
                                                    'Label on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                ); ?>
                                        </label>
                                        <input class="ecurring-hide"
                                               name="ecurring_resume_date"
                                               type="date"
                                               value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
                                        <button><?php
                                                echo esc_html_x(
                                                    'Update',
                                                    'Label on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                ); ?>
                                        </button>
                                    </div>
                                    <button class="resume-update ecurring-hide">
                                        <?php
                                            echo esc_html_x(
                                                'Update',
                                                'Label on the Subscriptions page in my account',
                                                'woo-ecurring'
                                            );
                                        ?></button>
                                <?php } ?>
                                <?php if ($this->allowOption('switch')) { ?>
                                    <div class="ecurring-hide switch-form"
                                         data-subscription="<?php echo esc_attr($subscription->getId()); ?>">
                                        <select class="ecurring_subscription_plan"
                                                name="ecurring_subscription_plan">
                                            <?php foreach ($products as $key => $value) { ?>
                                                <option value="<?php echo esc_attr($key); ?>"
                                                    <?php selected(
                                                        $subscription->getSubscriptionPlanId(),
                                                        $key
                                                    ); ?>
                                                ><?php echo esc_attr($value); ?></option>
                                            <?php }; ?>
                                        </select>
                                        <label><input name="ecurring_switch_subscription"
                                                      type="radio"
                                                      value="immediately" class="tog"
                                                      checked="checked"
                                            /><?php
                                                        echo esc_html_x(
                                                            'Immediately',
                                                            'Label on the Subscriptions page in my account',
                                                            'woo-ecurring'
                                                        ); ?></label>
                                        <label><input name="ecurring_switch_subscription"
                                                      type="radio"
                                                      value="specific-date"
                                                      class="tog"
                                            /><?php
                                                echo esc_html_x(
                                                    'Specific date',
                                                    'Label on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                ); ?></label>
                                        <input name="ecurring_switch_date" type="date"
                                               value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
                                        <button>Update</button>
                                    </div>
                                <?php } ?>
                                <?php if ($this->allowOption('cancel')) { ?>
                                    <div class="ecurring-hide cancel-form"
                                         data-subscription="<?php echo esc_attr($subscription->getId()); ?>">
                                        <label><input name="ecurring_cancel_subscription"
                                                      type="radio"
                                                      value="infinite" class="tog"
                                                      checked="checked"
                                            /><?php
                                                echo esc_html_x(
                                                    'Infinite',
                                                    'Label on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                ); ?></label>
                                        <label><input name="ecurring_cancel_subscription"
                                                      type="radio"
                                                      value="specific-date"
                                                      class="tog"
                                            /><?php
                                                echo esc_html_x(
                                                    'Specific date',
                                                    'Label on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                );
                                                ?></label>
                                        <input name="ecurring_cancel_date"
                                               type="date"
                                               value="<?php echo esc_attr((new DateTime('now'))->format('Y-m-d')); ?>">
                                        <button><?php
                                                echo esc_html_x(
                                                    'Update',
                                                    'Button title on the Subscriptions page in my account',
                                                    'woo-ecurring'
                                                );
                                                ?></button>
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

    /**
     * Check if customer allowed to perform any actions with subscription.
     *
     * @return bool
     */
    protected function allowAtLeastOneOption(): bool
    {
        return $this->allowOption('pause') ||
            $this->allowOption('switch') ||
            $this->allowOption('cancel');
    }

    /**
     * Check if customer allowed to do perform given action with subscription.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function allowOption(string $action): bool
    {
        $action = $this->settingsCrud->getOption("ecurring_customer_subscription_{$action}");

        return $action !== 'no';
    }
}
