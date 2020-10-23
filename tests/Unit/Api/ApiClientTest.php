<?php


namespace Ecurring\WooEcurringTests\Unit\Api;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurringTests\TestCase;
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
		$transactionWebhookUrl = 'http://ecurring.loc/transaction';
		$method = 'POST';

		$sut = new ApiClient($apiKey);

		$requestData = [
			'data' => [
				'type'       => 'subscription',
				'attributes' => [
					'customer_id'              => $ecurringCustomerId,
					'subscription_plan_id'     => $subscriptionPlanId,
					'transaction_webhook_url'  => $transactionWebhookUrl,
					'confirmation_sent'        => true,
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
			$transactionWebhookUrl
		);
	}

	public function testActivateSubscription()
	{
		$subscriptionId = 'subscription12345';
		$apiKey = 'apikey098765';
		$method = 'PATCH';
		$mandateAcceptedDate = date('c');
		$mandateCode = 'somemandatecode123';

		$requestData = [
			'data' => [
				'type' => 'subscription',
				'id' => $subscriptionId,
				'attributes' => [
					'status' => 'active',
					'mandate_accepted' => true,
					'mandate_accepted_date' => $mandateAcceptedDate,
					'mandate_code' => $mandateCode
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
				sprintf('https://api.ecurring.com/subscriptions/%1$s', $subscriptionId),
				$requestArgs
			)
		->andReturn(
			[
				'body' => '{"field": "value"}'
			]
		);

		$sut = new ApiClient($apiKey);

		$sut->activateSubscription($subscriptionId, $mandateCode, $mandateAcceptedDate);
	}
}
