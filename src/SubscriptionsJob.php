<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use eCurring_WC_Plugin;

class SubscriptionsJob
{
    /**
     * @var DataBasedSubscriptionFactoryInterface
     */
    protected $subscriptionFactory;
    /**
     * @var Subscriptions
     */
    protected $subscriptionsApiClient;
    /**
     * @var Actions Subscription actions.
     */
    private $actions;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * SubscriptionsJob constructor.
     *
     * @param Actions $actions
     * @param Repository $repository
     * @param DataBasedSubscriptionFactoryInterface $subscriptionFactory
     * @param Subscriptions $subscriptionsApiClient
     */
    public function __construct(
        Actions $actions,
        Repository $repository,
        DataBasedSubscriptionFactoryInterface $subscriptionFactory,
        Subscriptions $subscriptionsApiClient
    ) {
        $this->actions = $actions;
        $this->repository = $repository;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscriptionsApiClient = $subscriptionsApiClient;
    }

    public function init(): void
    {
        add_action(
            'init',
            static function () {
                if (wp_doing_ajax()) {
                    return;
                }
                if (! function_exists('as_unschedule_all_actions') || ! function_exists('as_enqueue_async_action')) {
                    return;
                }

                if (get_option('ecurring_import_finished') === '1') {
                    as_unschedule_all_actions('ecurring_import_subscriptions');
                    return;
                }

                if (as_next_scheduled_action('ecurring_import_subscriptions') === false) {
                    as_enqueue_async_action(
                        'ecurring_import_subscriptions',
                        [],
                        'ecurring'
                    );
                }
            }
        );

        add_action(
            'ecurring_import_subscriptions',
            [$this, 'importSubscriptions']
        );
    }

    /**
     * Do subscriptions import, one page (10 subscriptions by default) at once.
     */
    public function importSubscriptions(): void
    {
        $page = get_option('ecurring_subscriptions_page', 1);

        try{
            $subscriptions = $this->subscriptionsApiClient->getSubscriptions($page);
        }catch (EcurringException $exception){
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to get subscriptions from page %1$d. Exception was caught: %2$s',
                    $page,
                    $exception->getMessage()
                )
            );

            return;
        }


        if (! $subscriptions) {
            update_option('ecurring_import_finished', true);

            return;
        }

        foreach ($subscriptions as $subscription) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Preparing to save subscription %1$s.',
                    $subscription->getId()
                )
            );

            $this->repository->insert($subscription);
        }

        update_option('ecurring_subscriptions_page', ++$page);
    }
}
