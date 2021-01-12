<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use DateTime;
use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionPlanSwitcher\SubscriptionPlanSwitcherInterface;
use Exception;

use function in_the_loop;
use function add_action;
use function add_filter;
use function filter_input;
use function json_decode;
use function wp_die;

class MyAccount
{
    /**
     * @var SubscriptionPlanSwitcherInterface
     */
    protected $subscriptionPlanSwitcher;

    /**
     * @var Actions
     */
    private $actions;

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
     * @param Actions $actions
     * @param Repository $repository
     * @param Subscriptions $subscriptions
     * @param SubscriptionPlanSwitcherInterface $subscriptionPlanSwitcher
     */
    public function __construct(
        Actions $actions,
        Repository $repository,
        Subscriptions $subscriptions,
        SubscriptionPlanSwitcherInterface $subscriptionPlanSwitcher
    ) {

        $this->actions = $actions;
        $this->repository = $repository;
        $this->subscriptions = $subscriptions;
        $this->subscriptionPlanSwitcher = $subscriptionPlanSwitcher;
    }

    //phpcs:ignore Inpsyde.CodeQuality.FunctionLength.TooLong
    public function init(): void
    {
        add_filter(
            'woocommerce_account_menu_items',
            function ($items) {
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
                $this->doSubscriptionAction($this->actions);
            }
        );
    }

    /**
     * @param Actions $actions
     * @throws Exception
     */
    protected function doSubscriptionAction(
        Actions $actions
    ): void {

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
                $response = json_decode(
                    $actions->pause(
                        $subscriptionId,
                        $this->detectSubscriptionResumeDate()
                    )
                );
                $this->updatePostSubscription($response);
                break;
            case 'resume':
                $response = json_decode($actions->resume($subscriptionId));
                $this->updatePostSubscription($response);
                break;
            case 'switch':
                $this->doSubscriptionSwitch(
                    $subscriptionId,
                    $this->detectNewSubscriptionPlanId(),
                    $this->detectSubscriptionSwitchDate()
                );
                break;
            case 'cancel':
                $response = json_decode(
                    $actions->cancel($subscriptionId, $this->detectSubscriptionCancelDate())
                );
                $this->updatePostSubscription($response);
                break;
        }

        wp_die();
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
     * @return string Formatted subscription cancel date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionResumeDate(): string
    {
        $pauseSubscription = filter_input(
            INPUT_POST,
            'ecurring_pause_subscription',
            FILTER_SANITIZE_STRING
        );
        $resumeDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
        if ($pauseSubscription === 'specific-date') {
            $resumeDate = filter_input(
                INPUT_POST,
                'ecurring_resume_date',
                FILTER_SANITIZE_STRING
            );

            $resumeDate = (new DateTime($resumeDate))->format('Y-m-d\TH:i:sP');
        }

        return $resumeDate;
    }

    /**
     * Get formatted subscription cancel date from posted data.
     *
     * @return string Formatted subscription cancel date.
     *
     * @throws Exception If cannot create DateTime object.
     */
    protected function detectSubscriptionCancelDate(): string
    {
        $cancelSubscription = filter_input(
            INPUT_POST,
            'ecurring_cancel_subscription',
            FILTER_SANITIZE_STRING
        );
        $cancelDate = (new DateTime('now'))->format('Y-m-d\TH:i:sP');
        if ($cancelSubscription === 'specific-date') {
            $cancelDate = filter_input(
                INPUT_POST,
                'ecurring_cancel_date',
                FILTER_SANITIZE_STRING
            );

            $cancelDate = (new DateTime($cancelDate))->format('Y-m-d\TH:i:sP');
        }

        return $cancelDate;
    }

    /**
     * @param $subscriptionData
     *
     * @return void
     */
    protected function updatePostSubscription($subscriptionData): void
    {
        $this->repository->update($subscriptionData);
    }
}
