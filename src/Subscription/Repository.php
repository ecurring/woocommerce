<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription;

use DateTime;
use Ecurring\WooEcurring\Api\Customers;
use Ecurring\WooEcurring\Subscription\Mandate\SubscriptionMandateInterface;
use Ecurring\WooEcurring\Subscription\Status\SubscriptionStatusInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\DataBasedSubscriptionFactoryInterface;
use Ecurring\WooEcurring\Subscription\SubscriptionFactory\SubscriptionFactoryException;
use eCurring_WC_Helper_Api;
use eCurring_WC_Helper_Settings;
use eCurring_WC_Plugin;
use Exception;

class Repository
{
    /**
     * @var DataBasedSubscriptionFactoryInterface
     */
    protected $subscriptionFactory;

    /**
     * Repository constructor.
     *
     * @param DataBasedSubscriptionFactoryInterface $subscriptionFactory
     */
    public function __construct(
        DataBasedSubscriptionFactoryInterface $subscriptionFactory
    ) {

        $this->subscriptionFactory = $subscriptionFactory;
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
            $customer = $this->getCustomerApi();
            $customerDetails = $customer->getCustomerById(
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
     * Get the subscription by id.
     *
     * @param string $subscriptionId The id of the subscription to look for.
     *
     * @return SubscriptionInterface|null Found subscription.
     *
     * @throws SubscriptionFactoryException If cannot build a subscription from existing data.
     */
    public function getSubscriptionById(string $subscriptionId): ?SubscriptionInterface
    {
        $subscriptionPostId = $this->findSubscriptionPostIdBySubscriptionId($subscriptionId);

        if ($subscriptionPostId === 0) {
            return null;
        }

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
         * @var $found array<int>
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
