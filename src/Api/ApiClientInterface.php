<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Api;

/**
 * Service able to interact with eCurring API.
 */
interface ApiClientInterface
{

    /**
     * Make eCurring API request call.
     *
     * @param string     $method HTTP Method, one of the GET, POST, PATH, DELETE.
     * @param string     $url    Request target URL.
     * @param bool|array $data   Content to be sent in JSON-encoded format as request body.
     *
     * @return array Parsed response data.
     *
     * @throws ApiClientException
     */
    public function apiCall(string $method, string $url, $data = false): array;

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
}
