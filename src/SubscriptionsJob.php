<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use eCurring_WC_Plugin;

class SubscriptionsJob
{
    /**
     * @var DataBasedSubscriptionFactoryInterface
     */
    protected $subscriptionFactory;
    /**
     * @var Actions Subscription actions.
     */
    private $actions;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(
        Actions $actions,
        Repository $repository,
        DataBasedSubscriptionFactoryInterface $subscriptionFactory
    ) {
        $this->actions = $actions;
        $this->repository = $repository;
        $this->subscriptionFactory = $subscriptionFactory;
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

    public function importSubscriptions(): void
    {
        $page = get_option('ecurring_subscriptions_page', 1);

        $subscriptions = json_decode($this->actions->import((int)$page), true);

        $this->saveSubscriptionsBunch($subscriptions);

        $parts = parse_url($subscriptions['links']['next'] ?? '');
        parse_str($parts['query'] ?? '', $query);
        $nextPage = $query['page']['number'] ?? null;

        if ($nextPage  === null) {
            eCurring_WC_Plugin::debug(
                'Could not get the next page number from API response.' .
                'Subscriptions import failed.'
            );
        }

        update_option('ecurring_subscriptions_page', $nextPage);

        $parts = parse_url($subscriptions['links']['last']);
        parse_str($parts['query'] ?? '', $query);
        $lastPage = $query['page']['number'] ?? null;

        if ($nextPage >= $lastPage) {
            update_option('ecurring_import_finished', true);
        }
    }

    protected function saveSubscriptionsBunch($subscriptionsData): void
    {
        foreach ($subscriptionsData['data'] as $subscriptionFields) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Preparing to save subscription %1$s.',
                    $subscriptionFields->id
                )
            );

            try {
                $subscription = $this->subscriptionFactory->createSubscription(
                    $subscriptionFields['id'],
                    (array) $subscriptionFields['attributes']
                );
                $this->repository->insert($subscription);

            } catch (SubscriptionFactoryException $exception) {
                eCurring_WC_Plugin::debug(
                    sprintf(
                        'Couldn\'t create subscription instance from received data. Exception was caught with message: %1$s',
                        $exception->getMessage()
                    )
                );
            }

        }
    }
}
