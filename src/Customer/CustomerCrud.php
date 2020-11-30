<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\Customer;

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
        update_user_meta($localUserId, self::ECURRING_CUSTOMER_ID_STORAGE_KEY, $ecurringId);
    }

    /**
     * @inheritDoc
     */
    public function getEcurringId(int $localUserId): string
    {
        return (string) get_user_meta($localUserId, self::ECURRING_CUSTOMER_ID_STORAGE_KEY, true);
    }

    /**
     * @inheritDoc
     */
    public function saveMollieMandateId(int $localUserId, string $mollieMandateId): void
    {
        update_user_meta($localUserId, self::MOLLIE_MANDATE_ID_STORAGE_KEY, $mollieMandateId);
    }

    /**
     * @inheritDoc
     */
    public function getMollieMandateId(int $localUserId): string
    {
        return get_user_meta($localUserId, self::MOLLIE_MANDATE_ID_STORAGE_KEY, true);
    }

    /**
     * @inheritDoc
     */
    public function clearCustomerData(int $localUserId): void
    {
        delete_user_meta($localUserId, self::ECURRING_CUSTOMER_ID_STORAGE_KEY);
        delete_user_meta($localUserId, self::MOLLIE_MANDATE_ID_STORAGE_KEY);
    }
}
