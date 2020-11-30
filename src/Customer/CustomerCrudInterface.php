<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

/**
 * Service able to create/read/update/delete customer's data.
 */
interface CustomerCrudInterface
{
    /**
     * Save local user's id used in eCurring system.
     *
     * @param int    $localUserId Local user id.
     * @param string $ecurringId  eCurring user id to save.
     */
    public function saveEcurringId(int $localUserId, string $ecurringId): void;

    /**
     * Get local user's id used in eCurring system.
     *
     * @param int $wpUserId Local user id to get eCurring id of.
     *
     * @return string
     */
    public function getEcurringId(int $wpUserId): string;

    /**
     * Save Mollie mandate id associated with the local customer.
     *
     * @param int    $wpUserId Local user id.
     * @param string $mollieMandateId Mollie mandate id to save.
     */
    public function saveMollieMandateId(int $wpUserId, string $mollieMandateId): void;

    /**
     * @param int $wpUserId Local user id.
     *
     * @return string Mollie mandate id.
     */
    public function getMollieMandateId(int $wpUserId): string;

    /**
     * Delete all data associated with given customer.
     */
    public function clearCustomerData(int $wpUserId): void;
}
