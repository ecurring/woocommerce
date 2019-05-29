<?php

class eCurring_WC_Subscription
{
	/**
	 * eCurring subscriptions statuses
	 */
	const SUBSCRIPTION_STATUS_UNVERIFIED = 'unverified';
	const SUBSCRIPTION_STATUS_ACTIVE = 'active';
	const SUBSCRIPTION_STATUS_CANCELLED = 'cancelled';
	const SUBSCRIPTION_STATUS_PAUSED = 'paused';

	/**
	 * The status of the subscription.
	 *
	 * @var string
	 */
	public $status = self::SUBSCRIPTION_STATUS_UNVERIFIED;

	/**
	 * Subscription ID.
	 *
	 * @var number
	 */
	public $id;

	/**
	 * Subscription customer details.
	 *
	 * @var object
	 */
	protected $customer_id;

	public function __construct($subscription) {

		$this->status = $subscription['attributes']['status'];
		$this->id = $subscription['id'];
		$this->customer_id = $subscription['relationships']['customer']['data']['id'];
	}

	/**
	 * Is this subscription cancelled?
	 *
	 * @return bool
	 */
	public function cancelled() {

		return $this->status === self::SUBSCRIPTION_STATUS_CANCELLED;
	}

	/**
	 * Is this subscription paused?
	 *
	 * @return bool
	 */
	public function paused() {

		return $this->status === self::SUBSCRIPTION_STATUS_PAUSED;
	}

	/**
	 * Is this subscription still active?
	 *
	 * @return bool
	 */
	public function active() {

		return $this->status === self::SUBSCRIPTION_STATUS_ACTIVE;
	}

	/**
	 * Is this subscription unverified?
	 *
	 * @return bool
	 */
	public function unverified() {

		return $this->status === self::SUBSCRIPTION_STATUS_UNVERIFIED;
	}

	protected function getCustomer() {
		return eCurring_WC_Plugin::getDataHelper()->getCustomer($this->customer_id);
	}

	public function cardHolder() {

		$card_holder = $this->getCustomer()['attributes']['card_holder'];
		return $card_holder;
	}
}
