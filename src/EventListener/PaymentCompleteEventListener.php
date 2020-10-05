<?php


namespace Ecurring\WooEcurring\EventListener;


use Ecurring\WooEcurring\Api\ApiClientInterface;

class PaymentCompleteEventListener {
	/**
	 * @var ApiClientInterface
	 */
	protected $apiClient;

	/**
	 * @param ApiClientInterface $apiClient To make eCurring API calls.
	 */
	public function __construct(ApiClientInterface $apiClient){

		$this->apiClient = $apiClient;
	}

	public function init()
	{
		add_action('woocommerce_payment_complete', [$this, 'onPaymentComplete']);
	}
}
