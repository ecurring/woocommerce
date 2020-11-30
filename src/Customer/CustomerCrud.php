<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use WP_User;

/**
 * Customer crud using WP user meta as a storage.
 */
class CustomerCrud implements CustomerCrudInterface
{

    protected const ECURRING_CUSTOMER_ID_STORAGE_KEY = 'ecurring_customer_id';

    protected const MOLLIE_MANDATE_ID_STORAGE_KEY = '_ecurring_mollie_mandate_id';

    /**
     * @inheritDoc
     */
    public function saveEcurringId(int $localUserId, string $ecurringId): void
    {
        if (! $this->userExists($localUserId)) {
            throw new CustomerCrudException(
                sprintf(
                    'Couldn\'t save eCurring customer id for user %1$d because this user doesn\'t exist',
                    $localUserId
                )
            );
        }
        update_user_meta($localUserId, self::ECURRING_CUSTOMER_ID_STORAGE_KEY, $ecurringId);
    }

    /**
     * @inheritDoc
     */
    public function getEcurringId(int $localUserId): string
    {
        if (! $this->userExists($localUserId)) {
            throw new CustomerCrudException(
                sprintf(
                    'Couldn\'t get eCurring customer id for user %1$d because this user doesn\'t exist',
                    $localUserId
                )
            );
        }

        return (string) get_user_meta($localUserId, self::ECURRING_CUSTOMER_ID_STORAGE_KEY, true);
    }

    /**
     * @inheritDoc
     */
    public function saveMollieMandateId(int $localUserId, string $mollieMandateId): void
    {
        if (! $this->userExists($localUserId)) {
            throw new CustomerCrudException(
                sprintf(
                    'Couldn\'t save Mollie mandate id for user %1$d because this user doesn\'t exist',
                    $localUserId
                )
            );
        }

        update_user_meta($localUserId, self::MOLLIE_MANDATE_ID_STORAGE_KEY, $mollieMandateId);
    }

    /**
     * @inheritDoc
     */
    public function getMollieMandateId(int $localUserId): string
    {

        if (! $this->userExists($localUserId)) {
            throw new CustomerCrudException(
                sprintf(
                    'Couldn\'t get Mollie mandate id for user %1$d because this user doesn\'t exist',
                    $localUserId
                )
            );
        }

        return get_user_meta($localUserId, self::MOLLIE_MANDATE_ID_STORAGE_KEY, true);
    }

    /**
     * @inheritDoc
     */
    public function clearCustomerData(int $localUserId): void
    {
        if (! $this->userExists($localUserId)) {
            throw new CustomerCrudException(
                sprintf(
                    'Couldn\'t clear customer data for user %1$d because this user doesn\'t exist',
                    $localUserId
                )
            );
        }

        $result = delete_user_meta($localUserId, self::ECURRING_CUSTOMER_ID_STORAGE_KEY) &&
                    delete_user_meta($localUserId, self::MOLLIE_MANDATE_ID_STORAGE_KEY);

        if (! $result) {
            sprintf(
                'Couldn\'t clear customer data. Please, check %1$s and %2$s meta ' .
                'fields of user %3$d manually and delete them if needed.',
                self::ECURRING_CUSTOMER_ID_STORAGE_KEY,
                self::MOLLIE_MANDATE_ID_STORAGE_KEY,
                $localUserId
            );
        }
    }

    /**
     * Check if user with given ID exists.
     *
     * @return bool True for user exists, false otherwise.
     */
    protected function userExists(int $userId): bool
    {
        return WP_User::get_data_by('id', $userId) !== false;
    }
}
