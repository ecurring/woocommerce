<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use DateTime;
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
     * @var SubscriptionPlans
     */
    private $subscriptionPlans;

    /**
     * @param SubscriptionPlans $subscriptionPlans Subscription plans API client.
     * @param SettingsCrudInterface $settingsCrud Settings storage.
     * @param Repository $repository Subscriptions repository.
     */
    public function __construct(
        SubscriptionPlans $subscriptionPlans,
        SettingsCrudInterface $settingsCrud,
        Repository $repository
    ) {
        $this->subscriptionPlans = $subscriptionPlans;
        $this->settingsCrud = $settingsCrud;
        $this->repository = $repository;
    }

    public function display(): void
    {
        $customerId = get_user_meta(get_current_user_id(), 'ecurring_customer_id', true);
        $currentPage = (int) get_query_var('ecurring-subscriptions') ?: 1;

        $subscriptionsPerPage = (int) get_option('posts_per_page', 10);
        $subscriptionsList = $this->repository->getSubscriptionsByEcurringCustomerId($customerId, $currentPage, $subscriptionsPerPage);
        $customerSubscriptionsTotal = $this->repository->getSubscriptionsNumberForEcurringCustomer($customerId);
        $pagesTotal = (int) ceil($customerSubscriptionsTotal / $subscriptionsPerPage);

        $subscriptionPlans = $this->subscriptionPlans->getSubscriptionPlans();
        $subscriptionPlansData = $subscriptionPlans->data ?? [];
        $products = [];
        foreach ($subscriptionPlansData as $product) {
            $products[$product->id] = $product->attributes->name;
        }

        if (count($subscriptionsList) > 0) {
            $this->displaySubscriptionsTable($subscriptionsList, $products);
            $this->displayPagination($currentPage, $pagesTotal);
            return;
        }

        $this->displayNoSubscriptionsMessage();
    }

    /**
     * Render the table containing subscriptions of the current customer.
     *
     * @param array $subscriptionsList
     * @param array $products
     */
    protected function displaySubscriptionsTable(array $subscriptionsList, array $products)
    {
        ?>
        <table class="woocommerce-orders-table shop_table shop_table_responsive">
            <?php $this->displaySubscriptionsTableHead(); ?>
            <?php $this->displaySubscriptionsTableBody($subscriptionsList, $products) ?>
        </table> <?php
    }

    /**
     * Render heading of the subscriptions table for the current customer.
     */
    protected function displaySubscriptionsTableHead(): void
    {
        ?>
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
            </thead> <?php
    }

    /**
     * @param array $subscriptionsList Subscriptions to display in the table.
     * @param array $products Available subscription plans.
     * phpcs:disable
     */
    protected function displaySubscriptionsTableBody(array $subscriptionsList, array $products): void
    {
        //phpcs: enable
        ?>
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
                            <?php if ($subscription->getStatus()->getCurrentStatus() !== 'cancelled') { ?>
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
                                    <?php if ($subscription->getStatus()->getCurrentStatus() === 'paused') { ?>
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
                            <?php } else {
                                esc_html_e('No available options.', 'woo-ecurring');
                            } ?>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody> <?php
    }

    protected function displayPagination(int $currentPage, int $pagesTotal): void
    { ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
            <?php if ($currentPage > 1) : ?>
                <a
                        class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button"
                        href="<?php echo esc_url(wc_get_endpoint_url('ecurring-subscriptions', $currentPage - 1)); ?>">
                    <?php esc_html_e('Previous', 'woocommerce'); ?>
                </a>
            <?php endif; ?>

            <?php if ($currentPage < $pagesTotal) : ?>
                <a
                        class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button"
                        href="<?php echo esc_url(wc_get_endpoint_url('ecurring-subscriptions', $currentPage + 1)); ?>">
                    <?php esc_html_e('Next', 'woocommerce'); ?>
                </a>
            <?php endif; ?>
        </div> <?php
    }

    protected function displayNoSubscriptionsMessage(): void
    { ?>
        <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Browse products', 'woo-ecurring' ); ?></a>
		<?php esc_html_e( 'You have no subscriptions yet.', 'woo-ecurring' ); ?>
	    </div> <?php
    }

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
