<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use DateTime;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateInterface;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use eCurring_WC_Plugin;
use Exception;
use WP_Query;

class Repository
{
    /**
     * @var DataBasedSubscriptionFactoryInterface
     */
    protected $subscriptionFactory;
    /**
     * @var Customers
     */
    protected $customersApiClient;

    /**
     * Repository constructor.
     *
     * @param DataBasedSubscriptionFactoryInterface $subscriptionFactory
     * @param Customers $customersApiClient
     */
    public function __construct(
        DataBasedSubscriptionFactoryInterface $subscriptionFactory,
        Customers $customersApiClient
    ) {

        $this->subscriptionFactory = $subscriptionFactory;
        $this->customersApiClient = $customersApiClient;
    }

    public function insert(SubscriptionInterface $subscription, int $orderId): void
    {
        $subscriptionId = $subscription->getId();

        $subscriptionOrderId = $orderId ?: $this->findSubscriptionOrderIdBySubscriptionId($subscriptionId);

        $this->persistSubscription($subscription, $subscriptionOrderId);
    }

    protected function persistSubscription(SubscriptionInterface $subscription, int $orderId): void
    {
        $postId = wp_insert_post(
            [
                'post_type' => 'esubscriptions',
                'post_title' => $subscription->getId(),
                'post_status' => 'publish',
            ]
        );

        if ($postId && is_int($postId)) {
            $customerDetails = $this->customersApiClient->getCustomerById(
                $subscription->getCustomerId()
            );

            $this->saveSubscriptionData($postId, $subscription, $customerDetails);
            update_post_meta($postId, '_ecurring_post_subscription_order_id', $orderId);

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
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to update subscription %1$s: no existing subscription with this id was found.',
                    $subscription->getId()
                )
            );
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
            '_ecurring_post_subscription_links',
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
     * Get the subscription by id.
     *
     * @param string $subscriptionId The id of the subscription to look for.
     *
     * @return SubscriptionInterface|null Found subscription.
     *
     * @throws SubscriptionFactoryException If cannot build a subscription from existing data.
     */
    public function getSubscriptionById(string $subscriptionId): ?SubscriptionInterface //phpcs:ignore Inpsyde.CodeQuality.FunctionLength.TooLong
    {
        $subscriptionPostId = $this->findSubscriptionPostIdBySubscriptionId($subscriptionId);

        if ($subscriptionPostId === 0) {
            return null;
        }

        return $this->createSubscriptionFromPostId($subscriptionId, $subscriptionPostId);
    }

    /**
     * Return all subscriptions where eCurring customer id is the same as given.
     *
     * @param string $ecurringCustomerId
     * @param int $page Number of page (offset)
     * @param int $perPage Max number of subscriptions to return (limit), -1 for unlimited.
     *
     * @return Subscription[]
     */
    public function getSubscriptionsByEcurringCustomerId(string $ecurringCustomerId, int $page = 1, int $perPage = -1): array
    {

        $foundPostIds = $this->getSubscriptionPostIdsByEcuringCustomerId($ecurringCustomerId, $page, $perPage);

        return array_map(function (int $postId): ?SubscriptionInterface {
            $subscriptionId = get_post_meta($postId, '_ecurring_post_subscription_id', true);
            try {
                return $this->createSubscriptionFromPostId($subscriptionId, $postId);
            } catch (SubscriptionFactoryException $exception) {
                eCurring_WC_Plugin::debug(
                    sprintf(
                        'Failed to create subscription instance,' .
                        'post id %1$d. Caught exception message: %2$s',
                        $postId,
                        $exception->getMessage()
                    )
                );
            }
        }, $foundPostIds);
    }

    /**
     * Return number of subscriptions found locally for given eCurring customer.
     *
     * @param string $ecurringCustomerId The eCurring customer to search subscriptions for.
     *
     * @return int Subscriptions number.
     */
    public function getSubscriptionsNumberForEcurringCustomer(string $ecurringCustomerId): int
    {
        return count($this->getSubscriptionPostIdsByEcuringCustomerId($ecurringCustomerId));
    }

    /**
     * Return array of subscriptions post ids with optional limit and offset.
     *
     * @param string $ecurringCustomerId The eCurring customer to search subscriptions for.
     * @param int $page Page to start from.
     * @param int $perPage Max number of ids to return (limit).
     *
     * @return array Found ids.
     */
    protected function getSubscriptionPostIdsByEcuringCustomerId(string $ecurringCustomerId, int $page = 1, int $perPage = -1): array
    {
        if ($ecurringCustomerId === '') {
            return [];
        }

        $query = new WP_Query();

        /** @var int[] */
        return $query->query(
            [
                'post_type' => 'esubscriptions',
                'orderby' => 'date',
                'order' => 'DESC',
                'posts_per_page' => $perPage,
                'paged' => $page,
                'fields' => 'ids',
                'post_status' => 'publish',
                'meta_key' => '_ecurring_post_subscription_customer_id',
                'meta_value' => $ecurringCustomerId,
            ]
        );
    }

    /**
     * Create a subscription instance from WP post.
     *
     * @param string $subscriptionId The id of the subscription in eCurring.
     * @param int $subscriptionPostId The id of the WP post used to store subscription data.
     *
     * @return SubscriptionInterface
     *
     * @throws SubscriptionFactoryException
     */
    protected function createSubscriptionFromPostId(string $subscriptionId, int $subscriptionPostId): SubscriptionInterface
    {
        $startDate = get_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_start_date',
            true
        );
        $cancelDate = get_post_meta($subscriptionPostId, '_ecurring_post_subscription_cancel_date', true);
        $mandateAcceptedDate = get_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_mandate_accepted_date',
            true
        );
        $resumeDate = get_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_resume_date',
            true
        );

        $createdAt = get_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_created_at',
            true
        );

        $updatedAt = get_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_updated_at',
            true
        );

        $subscriptionData = [
            'subscription_id' => $subscriptionId,
            'customer_id' => get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_customer_id',
                true
            ),
            'subscription_plan_id' => get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_plan_id',
                true
            ),
            'mandate_code' => get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_mandate_code',
                true
            ),
            'status' => get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_status',
                true
            ),
            'confirmation_page' => get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_mandate_confirmation_page',
                true
            ),
            'confirmation_sent' => (bool) get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_mandate_confirmation_sent',
                true
            ),
            'mandate_accepted' => (bool) get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_mandate_accepted',
                true
            ),
            'archived' => (bool) get_post_meta(
                $subscriptionPostId,
                '_ecurring_post_subscription_archived',
                true
            ),
            'mandate_accepted_date' => $this->createDateFromString($mandateAcceptedDate),
            'start_date' => $this->createDateFromString($startDate),
            'cancel_date' => $this->createDateFromString($cancelDate),
            'resume_date' => $this->createDateFromString($resumeDate),
            'created_at' => $this->createDateFromString($createdAt),
            'updated_at' => $this->createDateFromString($updatedAt),
        ];

        return $this->subscriptionFactory->createSubscription($subscriptionData);
    }

    /**
     * Create a DateTime object from string.
     *
     * @param string $date
     *
     * @return DateTime|null
     */
    protected function createDateFromString(string $date): ?DateTime
    {
        try {
            $dateTime = new DateTime($date);
        } catch (Exception $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to create a DateTime object from string. Exception caught: %1$s',
                    $exception->getMessage()
                )
            );

            return null;
        }

        return $dateTime;
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
            $mandateAcceptedDate ? $mandateAcceptedDate->format('Y-m-d\TH:i:sP') : ''
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
     * @param string $subscriptionId
     *
     * @return int
     */
    public function findSubscriptionPostIdBySubscriptionId(string $subscriptionId): int
    {
        if ($subscriptionId === '') {
            return 0;
        }

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
     * Return an id of the order containing given subscription, return 0 if not found.
     *
     * @param string $subscriptionId Subscription id to find order with.
     *
     * @return int Found order id.
     */
    public function findSubscriptionOrderIdBySubscriptionId(string $subscriptionId): int
    {
        $subscriptionPostId = $this->findSubscriptionPostIdBySubscriptionId($subscriptionId);

        return (int) get_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_order_id',
            true
        );
    }

    public function findSubscriptionIdByOrderId(int $orderId): string
    {
        /**
         * @var array<int> $found
         */
        $found = get_posts(
            [
                'numberposts' => 1,
                'post_type' => 'esubscriptions',
                'meta_key' => '_ecurring_post_subscription_order_id',
                'meta_value' => $orderId,
                'fields' => 'ids',
            ]
        );

        $subscriptionPostId = $found[0] ?? 0;

        return (string) get_post_meta(
            $subscriptionPostId,
            '_ecurring_post_subscription_id',
            true
        );
    }
}
