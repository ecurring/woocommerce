<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateInterface;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusInterface;
use eCurring_WC_Helper_Api;
use eCurring_WC_Helper_Settings;
use eCurring_WC_Plugin;

class Repository
{

    public function insert(SubscriptionInterface $subscription): void
    {
        $subscriptionId = $subscription->getId();

        if ($this->subscriptionExistsInDb($subscriptionId)) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Subscription %1$s already exists in local database, ' .
                    'saving will be skipped.',
                    $subscriptionId
                )
            );
            return;
        }
        if (! $this->orderWithSubscriptionExists($subscriptionId)) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Order not found for the subscription %1$s, saving will be skipped.',
                    $subscriptionId
                )
            );
            return;
        }

        $this->persistSubscription($subscription);
    }

    protected function persistSubscription(SubscriptionInterface $subscription): void
    {
        $postId = wp_insert_post(
            [
                'post_type' => 'esubscriptions',
                'post_title' => $subscription->getId(),
                'post_status' => 'publish',
            ]
        );

        if ($postId && is_int($postId)) {
            $customer = $this->getCustomerApi();
            $customerDetails = $customer->getCustomerById(
                $subscription->getCustomerId()
            );

            $this->saveSubscriptionData($postId, $subscription, $customerDetails);

            eCurring_WC_Plugin::debug(
                sprintf(
                    'Subscription %1$s successfully saved as post %2$d',
                    $subscription->getId(),
                    $postId
                )
            );
        }
    }

    public function update(SubscriptionInterface $subscription): void
    {
        $subscriptionPostId = $this->findSubscriptionPostIdBySubscriptionId($subscription->getId());
        if ($subscriptionPostId === 0) {
            return;
        }

        $this->saveSubscriptionData($subscriptionPostId, $subscription);
    }

    protected function saveSubscriptionData(
        int $subscriptionPostId,
        SubscriptionInterface $subscription,
        $customerDetails = null
    ): void {
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_id',
            $subscription->getId()
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_links', //todo: save subscription url instead or build it using subscription id.
            []
        );
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_customer_id',
            $subscription->getCustomerId()
        );

        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_plan_id',
            $subscription->getSubscriptionPlanId()
        );

        $this->saveMandateFields($subscriptionPostId, $subscription->getMandate());
        $this->saveSubscriptionStatusFields($subscriptionPostId, $subscription->getStatus());

        if ($customerDetails !== null) {
            update_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_customer',
                $customerDetails
            );
        }
    }

    /**
     * @param int $subscriptionPostId
     * @param SubscriptionMandateInterface $mandate
     */
    protected function saveMandateFields(int $subscriptionPostId, SubscriptionMandateInterface $mandate): void
    {
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_mandate_accepted',
            $mandate->getAccepted()
        );

        $mandateAcceptedDate = $mandate->getAcceptedDate();
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_mandate_accepted_date',
            $mandateAcceptedDate ? $mandateAcceptedDate->format('c') : ''
        );

        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_mandate_confirmation_page',
            $mandate->getConfirmationPageUrl()
        );

        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_mandate_confirmation_sent',
            $mandate->getConfirmationSent()
        );

        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_mandate_code',
            $mandate->getMandateCode()
        );
    }

    protected function saveSubscriptionStatusFields(
        int $subscriptionPostId,
        SubscriptionStatusInterface $subscriptionStatus
    ): void {
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_status',
            $subscriptionStatus->getCurrentStatus()
        );

        $startDate = $subscriptionStatus->getStartDate();

        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_start_date',
            $startDate ? $startDate->format('c') : ''
        );

        $cancelDate = $subscriptionStatus->getCancelDate();
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_cancel_date',
            $cancelDate ? $cancelDate->format('c') : ''
        );

        $resumeDate = $subscriptionStatus->getResumeDate();
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_resume_date',
            $resumeDate ? $resumeDate->format('c') : ''
        );

        $createdAt = $subscriptionStatus->getCreatedAt();
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_created_at',
            $createdAt ? $createdAt->format('c') : ''
        );

        $updatedAt = $subscriptionStatus->getUpdatedAt();
        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_updated_at',
            $updatedAt ? $updatedAt->format('c') : ''
        );

        update_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_archived',
            $subscriptionStatus->getArchived()
        );
    }

    /**
     * Check if subscription already saved in the local DB.
     *
     * @param string $subscriptionId The subscription id to check for.
     *
     * @return bool
     */
    protected function subscriptionExistsInDb(string $subscriptionId): bool
    {
        $found = $this->findSubscriptionPostIdBySubscriptionId($subscriptionId);

        return $found !== 0;
    }

    /**
     * @param string $subscriptionId
     *
     * @return int
     */
    protected function findSubscriptionPostIdBySubscriptionId(string $subscriptionId): int
    {
        /** @var int[] $found */
        $found = get_posts(
            [
                'post_type' => 'esubscriptions',
                'numberposts' => 1,
                'post_status' => 'publish',
                'fields' => 'ids',
                'meta_key' => '_ecurring_post_subscription_id',
                'meta_value' => $subscriptionId,
            ]
        );

        return $found[0] ?? 0;
    }

    /**
     * Check if order with the given subscription id exists.
     *
     * @param string $subscriptionId The id of the subscription to look for.
     *
     * @return bool True if order containing given subscription id exists, false otherwise.
     */
    protected function orderWithSubscriptionExists(string $subscriptionId): bool
    {
        $foundOrderId = $this->findSubscriptionOrderIdBySubscriptionId($subscriptionId);

        return $foundOrderId !== 0;
    }

    /**
     * Return an id of the order containing given subscription, return 0 if not found.
     *
     * @param string $subscriptionId Subscription id to find order with.
     *
     * @return int Found order id.
     */
    protected function findSubscriptionOrderIdBySubscriptionId(string $subscriptionId): int
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
     * @return Customers
     */
    protected function getCustomerApi(): Customers
    {
        $settingsHelper = new eCurring_WC_Helper_Settings();
        $api = new eCurring_WC_Helper_Api($settingsHelper);
        $customer = new Customers($api);
        return $customer;
    }
}
