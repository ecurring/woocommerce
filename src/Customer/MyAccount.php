<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use DateTime;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\StatusSwitcher\SubscriptionStatusSwitcherInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher\SubscriptionPlanSwitcherInterface;
use Exception;

use function in_the_loop;
use function add_action;
use function add_filter;
use function filter_input;
use function wp_die;

class MyAccount
{
    /**
     * @var SubscriptionPlanSwitcherInterface
     */
    protected $subscriptionPlanSwitcher;
    /**
     * @var SubscriptionStatusSwitcherInterface
     */
    protected $subscriptionStatusSwitcher;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var Subscriptions
     */
    private $subscriptions;

    /**
     * MyAccount constructor.
     *
     * @param Repository $repository
     * @param Subscriptions $subscriptions
     * @param SubscriptionPlanSwitcherInterface $subscriptionPlanSwitcher
     * @param SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher
     */
    public function __construct(
        Repository $repository,
        Subscriptions $subscriptions,
        SubscriptionPlanSwitcherInterface $subscriptionPlanSwitcher,
        SubscriptionStatusSwitcherInterface $subscriptionStatusSwitcher
    ) {

        $this->repository = $repository;
        $this->subscriptions = $subscriptions;
        $this->subscriptionPlanSwitcher = $subscriptionPlanSwitcher;
        $this->subscriptionStatusSwitcher = $subscriptionStatusSwitcher;
    }

    //phpcs:ignore Inpsyde.CodeQuality.FunctionLength.TooLong
    public function init(): void
    {
        add_filter(
            'woocommerce_account_menu_items',
            static function ($items) {
                $newItems = [];
                $newItems['ecurring-subscriptions'] = __('Subscriptions', 'woo-ecurring');
                $position = (int) array_search('orders', array_keys($items), true) + 1;

                $finalItems = array_slice($items, 0, $position, true);
                $finalItems += $newItems;
                $finalItems += array_slice($items, $position, count($items) - $position, true);

                return $finalItems;
            }
        );

        add_action(
            'woocommerce_account_ecurring-subscriptions_endpoint',
            function () {
                $this->subscriptions->display();
            }
        );

        add_filter(
            'query_vars',
            static function ($vars) {
                $vars[] = 'ecurring-subscriptions';
                return $vars;
            },
            0
        );

        add_filter(
            'the_title',
            static function ($title) {
                global $wp_query;
                if (isset($wp_query->query_vars['ecurring-subscriptions']) && in_the_loop()) {
                    return __('Subscriptions', 'woo-ecurring');
                }

                return $title;
            },
            10,
            2
        );

        add_action(
            'wp_ajax_ecurring_customer_subscriptions',
            function () {
                $this->doSubscriptionAction();
                wp_die();
            }
        );
    }

    /**
     * @throws Exception
     */
    protected function doSubscriptionAction(): void
    {

        $subscriptionType = filter_input(
            INPUT_POST,
            'ecurring_subscription_type',
            FILTER_SANITIZE_STRING
        );

        $subscriptionId = filter_input(
            INPUT_POST,
            'ecurring_subscription_id',
            FILTER_SANITIZE_STRING
        );

        switch ($subscriptionType) {
            case 'pause':
                $this->subscriptionStatusSwitcher->pause(
                    $subscriptionId,
                    $this->detectSubscriptionResumeDate()
                );
                break;
            case 'resume':
                $this->subscriptionStatusSwitcher->resume($subscriptionId);
                break;
            case 'switch':
                $this->doSubscriptionSwitch(
                    $subscriptionId,
                    $this->detectNewSubscriptionPlanId(),
                    $this->detectSubscriptionSwitchDate()
                );
                break;
            case 'cancel':
                $this->subscriptionStatusSwitcher->cancel(
                    $subscriptionId,
                    $this->detectSubscriptionCancelDate()
                );
                break;
        }
    }

    protected function doSubscriptionSwitch(string $subscriptionId, string $newSubscriptionPlanId, DateTime $switchDate): void
    {
        $subscription = $this->repository->getSubscriptionById($subscriptionId);

        $this->subscriptionPlanSwitcher->switchSubscriptionPlan($subscription, $newSubscriptionPlanId, $switchDate);
    }

    /**
     * Get formatted subscription switch date from posted data.
     *
     * @return DateTime Subscription switch date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionSwitchDate(): DateTime
    {
        $switchSubscription = filter_input(
            INPUT_POST,
            'ecurring_switch_subscription',
            FILTER_SANITIZE_STRING
        );
        $switchDate = (new DateTime('now'));
        if ($switchSubscription === 'specific-date') {
            $switchDate = filter_input(
                INPUT_POST,
                'ecurring_switch_date',
                FILTER_SANITIZE_STRING
            );

            $switchDate = (new DateTime($switchDate));
        }

        return $switchDate;
    }

    /**
     * @return string
     */
    public function detectNewSubscriptionPlanId(): string
    {
        return (string) filter_input(
            INPUT_POST,
            'ecurring_subscription_plan',
            FILTER_SANITIZE_STRING
        );
    }

    /**
     * Get formatted subscription resume date from posted data.
     *
     * @return DateTime|null Subscription resume date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionResumeDate(): ?DateTime
    {
        $pauseSubscription = filter_input(
            INPUT_POST,
            'ecurring_pause_subscription',
            FILTER_SANITIZE_STRING
        );
        $resumeDate = null;
        if ($pauseSubscription === 'specific-date') {
            $resumeDate = filter_input(
                INPUT_POST,
                'ecurring_resume_date',
                FILTER_SANITIZE_STRING
            );

            $resumeDate = new DateTime($resumeDate);
        }

        return $resumeDate;
    }

    /**
     * Get formatted subscription cancel date from posted data.
     *
     * @return DateTime Subscription cancel date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionCancelDate(): DateTime
    {
        $cancelSubscription = filter_input(
            INPUT_POST,
            'ecurring_cancel_subscription',
            FILTER_SANITIZE_STRING
        );
        $cancelDate = new DateTime('now');
        if ($cancelSubscription === 'specific-date') {
            $cancelDate = filter_input(
                INPUT_POST,
                'ecurring_cancel_date',
                FILTER_SANITIZE_STRING
            );

            $cancelDate = new DateTime($cancelDate);
        }

        return $cancelDate;
    }
}
