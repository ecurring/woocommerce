<?php

namespace Ecurring\WooEcurring;

use Ecurring\WooEcurring\Subscription\Actions;
use Ecurring\WooEcurring\Subscription\Repository;

class SubscriptionsJob
{
    /**
     * @var Actions Subscription actions.
     */
    private $actions;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(Actions $actions, Repository $repository)
    {
        $this->actions = $actions;
        $this->repository = $repository;
    }

    public function init()
    {
        add_action(
            'init',
            function () {
                if (wp_doing_ajax()) {
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
            function () {

                $page = get_option('ecurring_subscriptions_page', 1);

                $subscriptions = json_decode($this->actions->import((int)$page));

                $this->repository->createSubscriptions($subscriptions);

                $parts = parse_url($subscriptions->links->next);
                parse_str($parts['query'], $query);
                $nextPage = $query['page']['number'];

                update_option('ecurring_subscriptions_page', $nextPage);

                if (!$nextPage) {
                    update_option('ecurring_import_finished', true);
                }
            }
        );
    }
}