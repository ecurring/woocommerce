<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Subscription\SubscriptionFactory;

use Ecurring\WooEcurring\Subscription\SubscriptionInterface;

/**
 * Service able to create a subscription instance from data.
 */
interface DataBasedSubscriptionFactoryInterface
{
    /**
     * Create a new subscription from given data array.
     *
     * @param array $subscriptionData A list of subscription data fields.
     *      $subscriptionData = [
     *          'subscription_id' => (string) The id of the subscription in the eCurring system.
     *          'customer_id' => (string) The customer id in the eCurring system.
     *          'subscription_plan_id' => (string) The subscription plan (product)
     *                                                  id in the eCurring system.
     *          'mandate_code' => => (string) The eCurring mandate code.
     *          'status' => (string) Current subscription status.
     *          'confirmation_sent' => (bool) Whether confirmation email was sent to user.
     *          'confirmation_page' => (string) The URL of the page where user can
     *                                          accept subscription mandate.
     *          'mandate_accepted' => (bool) Whether mandate was accepted by the user.
     *          'mandate_accepted_date' => (DateTime|null) The date when mandate
     *                                                   was accepted or null if it wasn't.
     *          'start_date' => (DateTime|null) The date when subscription was/will be started.
     *          'cancel_date' => (DateTime|null) The date when subscription was/will be cancelled.
     *          'resume_date' => (DateTime|null) The date when subscription will be resumed if it's
     *                                                                           paused currently.
     *           'created_at' => (DateTime|null) The date when subscription was created.
     *           'updated_at' => (DateTime|null) The date when subscription was updated last time.
     *           'archived' => (bool) Whether subscription was archived.
     *      ]
     *
     * @see https://docs.ecurring.com/subscriptions/get/ eCurring documentation for more detailed fields description.
     *
     * @return SubscriptionInterface Created subscription instance.
     *
     * @throws SubscriptionFactoryException If cannot create a new subscription instance.
     */
    public function createSubscription(array $subscriptionData): SubscriptionInterface;
}
