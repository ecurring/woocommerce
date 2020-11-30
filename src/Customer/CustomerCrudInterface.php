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
    public function saveEcurringId(int $localUserId, string $ecurringId): void;

    /**
     * Get local user's id used in eCurring system.
     *
     * @param int $localUserId Local user id to get eCurring id of.
     *
     * @return string
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
     * @throws CustomerCrudException Thrown if couldn't clear user data.
     */
    public function clearCustomerData(int $localUserId): void;
}
