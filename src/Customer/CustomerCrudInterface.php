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
     *
     * @throws CustomerCrudException Thrown if couldn't save data.
     */
    public function saveEcurringCustomerId(int $localUserId, string $ecurringId): void;

    /**
     * Get local user's id used in eCurring system.
     *
     * @param int $localUserId Local user id to get eCurring id of.
     *
     * @return string Customer id or empty string if not set.
     *
     * @throws CustomerCrudException Thrown if couldn't get data.
     */
    public function getEcurringCustomerId(int $localUserId): string;

    /**
     * Save Mollie mandate id associated with the local customer.
     *
     * @param int    $localUserId     Local user id.
     * @param string $mollieMandateId Mollie mandate id to save.
     *
     * @throws CustomerCrudException Thrown if couldn't save data.
     */
    public function saveMollieMandateId(int $localUserId, string $mollieMandateId): void;

    /**
     * @param int $localUserId Local user id.
     *
     * @return string Mollie mandate id.
     *
     * @throws CustomerCrudException Thrown if couldn't get data.
     */
    public function getMollieMandateId(int $localUserId): string;

    /**
     * Delete all data associated with given customer.
     *
     * @param int $localUserId User id to clear data of.
     *
     * @throws CustomerCrudException Thrown if couldn't clear user data.
     */
    public function clearCustomerData(int $localUserId): void;

    /**
     * Set or remove flag to indicate if customer needs Mollie mandate to be added.
     *
     * @param int  $localUserId User id to save flag for.
     * @param bool $needsMollieMandate True to set flag, false to remove it.
     *
     * @throws CustomerCrudException Thrown if couldn't save data.
     */
    public function saveFlagCustomerNeedsMollieMandate(int $localUserId, bool $needsMollieMandate): void;

    /**
     * Check if given customer needs Mollie mandate to be added.
     *
     * @param int $localUserId User id to check flag for.
     *
     * @return bool True if customer needs Mollie mandate, false otherwise.
     *
     * @throws CustomerCrudException Thrown if couldn't get data.
     */
    public function getFlagCustomerNeedsMollieMandate(int $localUserId): bool;
}