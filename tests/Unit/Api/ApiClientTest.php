<?php


namespace eCurring\WooEcurringTests\Unit\Api;

use Ecurring\WooEcurring\Api\ApiClient;
use eCurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use function Brain\Monkey\Functions\expect;

class ApiClientTest extends TestCase {

	//This trait usage is needed so PhpUnit can detect expect() function as assertion.
	use MockeryPHPUnitIntegration;

	public function testCreateSubscription()
	{
		$apiKey = 'someapikey135';
		$ecurringCustomerId = 'ecurringcustomer123';
		$subscriptionPlanId = 'subscription564';
		$subscriptionWebhookUrl = 'http://ecurring.loc/subscription';
		$transactionWebhookUrl = 'http://ecurring.loc/transaction';
		$method = 'POST';

		$sut = new ApiClient($apiKey);

		$requestData = [
			'data' => [
				'type'       => 'subscription',
				'attributes' => [
					'customer_id'              => $ecurringCustomerId,
					'subscription_plan_id'     => $subscriptionPlanId,
					'subscription_webhook_url' => $subscriptionWebhookUrl,
					'transaction_webhook_url'  => $transactionWebhookUrl,
					'confirmation_sent'        => true,
					'mandate_accepted'         => true,
					'mandate_accepted_date'    => date('c'),
					'status'                   => 'active',
					'metadata'                 => ['source' => 'woocommerce']
				]
			]
		];

		$requestArgs = [
			'method'  => $method,
			'headers' => [
				'X-Authorization' => $apiKey,
				'Content-Type'    => 'application/vnd.api+json',
				'Accept'          => 'application/vnd.api+json'
			],
			'body' => json_encode($requestData)
		];

		expect('wp_remote_request')
			->once()
			->with(
				'https://api.ecurring.com/subscriptions',
				$requestArgs
			)
			->andReturn([
				'body' => '{"field": "value"}'
			]);

		$sut->createSubscription(
			$ecurringCustomerId,
			$subscriptionPlanId,
			$subscriptionWebhookUrl,
			$transactionWebhookUrl
		);
	}
}
