<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring;

use Ecurring\WooEcurring\Api\Subscriptions;
use Ecurring\WooEcurring\Subscription\Repository;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionInterface;
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
     * @var Repository
     */
    private $repository;

    /**
     * SubscriptionsJob constructor.
     *
     * @param Repository $repository
     * @param DataBasedSubscriptionFactoryInterface $subscriptionFactory
     * @param Subscriptions $subscriptionsApiClient
     */
    public function __construct(
        Repository $repository,
        DataBasedSubscriptionFactoryInterface $subscriptionFactory,
        Subscriptions $subscriptionsApiClient
    ) {
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
        $page = (int) get_option('ecurring_subscriptions_page', 1);

        try {
            $subscriptions = $this->subscriptionsApiClient->getSubscriptions($page);
        } catch (EcurringException $exception) {
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

            $this->handleSubscriptionSaving($subscription);
        }

        update_option('ecurring_subscriptions_page', ++$page);
    }

    /**
     * Save subscription if containing order will be found.
     *
     * @param SubscriptionInterface $subscription
     */
    protected function handleSubscriptionSaving(SubscriptionInterface $subscription): void
    {
        $subscriptionOrderId = $this->findOrderIdBySubscriptionId($subscription->getId());

        if ($subscriptionOrderId === 0 && $this->subscriptionIsFromCurrentDomain($subscription)) {
            $subscriptionOrderId = isset($subscription->getMeta()['order_id']) ?
                (int) $subscription->getMeta()['order_id'] :
                0;
        }

        if ($subscriptionOrderId === 0) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Order not found for the subscription %1$s, saving will be skipped.',
                    $subscription->getId()
                )
            );
            return;
        }

        $this->repository->insert($subscription, $subscriptionOrderId);
    }

    /**
     * Find the id of the order containing subscription.
     *
     * This works for the orders made before the version 2.0 of the plugin.
     *
     * @param string $subscriptionId
     *
     * @return int
     */
    protected function findOrderIdBySubscriptionId(string $subscriptionId): int
    {
        $addSubscriptionIdMetaSupport = static function (array $wpQueryArgs, array $wcOrdersQueryArgs) use ($subscriptionId): array {
            if (! empty($wcOrdersQueryArgs['_ecurring_subscription_id'])) {
                $wpQueryArgs['meta_query'][] = [
                    'key' => '_ecurring_subscription_id',
                    'value' => $subscriptionId,
                ];
            }

            return $wpQueryArgs;
        };

        add_filter(
            'woocommerce_order_data_store_cpt_get_orders_query',
            $addSubscriptionIdMetaSupport,
            10,
            2
        );

        /** @var array $foundIds */
        $foundIds = wc_get_orders(
            [
                'limit' => 1,
                'return' => 'ids',
                '_ecurring_subscription_id' => $subscriptionId,
            ]
        );

        remove_filter(
            'woocommerce_order_data_store_cpt_get_orders_query',
            $addSubscriptionIdMetaSupport
        );

        return $foundIds[0] ?? 0;
    }

    /**
     * Check if subscription meta contain current site url.
     *
     * @param SubscriptionInterface $subscription
     *
     * @return bool
     */
    protected function subscriptionIsFromCurrentDomain(SubscriptionInterface $subscription): bool
    {
        $subscriptionMeta = $subscription->getMeta();
        if(! isset($subscriptionMeta['shop_url']) || ! is_string($subscriptionMeta['shop_url'])){
            return false;
        }

        $subscriptionDomain = parse_url($subscriptionMeta['shop_url'], PHP_URL_HOST);
        $siteDomain = parse_url(get_site_url(get_current_blog_id()), PHP_URL_HOST);

        return $subscriptionDomain === $siteDomain;
    }
}
