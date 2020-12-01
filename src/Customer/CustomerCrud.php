<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

use WP_User;

/**
 * Customer crud using WP user meta as a storage.
 */
class CustomerCrud implements CustomerCrudInterface
{
    /**
     * This key has no underscore at the beginning because it was used in previous versions.
     */
    protected const ECURRING_CUSTOMER_ID_STORAGE_KEY = 'ecurring_customer_id';

    protected const MOLLIE_MANDATE_ID_STORAGE_KEY = '_ecurring_mollie_mandate_id';

    protected const FLAG_MOLLIE_MANDATE_NEEDED_KEY = '_ecurring_customer_needs_mollie_mandate';

    /**
     * @inheritDoc
     */
    public function saveEcurringCustomerId(int $localUserId, string $ecurringId): void
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
    public function getEcurringCustomerId(int $localUserId): string
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
     * @inheritDoc
     */
    public function saveFlagCustomerNeedsMollieMandate(int $localUserId, bool $needsMollieMandate): void
    {
        if (! $this->userExists($localUserId)) {
            throw new CustomerCrudException(
                sprintf(
                    'Couldn\'t set customer needs Mollie mandate flag ' .
                    'for user %1$d because this user doesn\'t exist',
                    $localUserId
                )
            );
        }

        if ($needsMollieMandate) {
            update_user_meta($localUserId, self::FLAG_MOLLIE_MANDATE_NEEDED_KEY, '1');
            return;
        }

        delete_user_meta($localUserId, self::FLAG_MOLLIE_MANDATE_NEEDED_KEY);
    }

    /**
     * @inheritDoc
     */
    public function getFlagCustomerNeedsMollieMandate(int $localUserId): bool
    {
        if (! $this->userExists($localUserId)) {
            throw new CustomerCrudException(
                sprintf(
                    'Couldn\'t get customer needs Mollie mandate flag ' .
                    'for user %1$d because this user doesn\'t exist',
                    $localUserId
                )
            );
        }

        return (bool) get_user_meta($localUserId, self::FLAG_MOLLIE_MANDATE_NEEDED_KEY, true);
    }

    /**
     * Check if user with given ID exists.
     *
     * @param int $userId User id to check.
     *
     * @return bool True for user exists, false otherwise.
     */
    protected function userExists(int $userId): bool
    {
        return WP_User::get_data_by('id', $userId) !== false;
    }
}
