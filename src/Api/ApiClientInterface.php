<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Api;

/**
 * Service able to interact with eCurring API.
 */
interface ApiClientInterface
{

    /**
     * API call to create subscription.
     *
     * @param string $ecurringCustomerId    eCurring customer id.
     * @param string $subscriptionPlanId    eCurring subscription product id.
     * @param array<array-key, mixed> $attributes Optional attributes array.
     *
     * @return array Created subscription data or error details.
     *
     * @throws ApiClientException If request failed.
     */
    public function createSubscription(
        string $ecurringCustomerId,
        string $subscriptionPlanId,
        array $attributes = []
    ): array;

    /**
     * @param string $subscriptionId
     *
     * @return array Subscription data or request error details.
     *
     * @throws ApiClientException If request failed.
     */
    public function getSubscriptionById(string $subscriptionId): array;

    /**
     * Add Mollie mandate id to the eCurring customer.
     *
     * @param string $customerId      eCurring customer id to add mandate to.
     * @param string $mollieMandateId Mollie mandate id to add.
     *
     * @return array Data accepted by the API.
     *
     * @throws ApiClientException If request failed.
     */
    public function addMollieMandateToTheEcurringCustomer(string $customerId, string $mollieMandateId): array;

    /**
     * Create an eCurring customer with given attributes.
     *
     * @param array $customerAttributes Attributes to use for create customer API call.
     *
     * @throws ApiClientException If request failed.
     *
     * @return array Created customer data.
     */
    public function createCustomer(array $customerAttributes): array;
}
