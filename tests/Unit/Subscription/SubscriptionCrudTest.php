<?php

namespace eCurring\WooEcurringTests\Unit\Subscription;

use Ecurring\WooEcurring\Subscription\SubscriptionCrud;
use eCurring\WooEcurringTests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Order;
use function Brain\Monkey\Functions\when;

class SubscriptionCrudTest extends TestCase {

	/**
	 * @dataProvider subscriptionDataProvider
	 */
	public function testSaveSubscription(array $subscriptionData, $subscriptionId)
	{
		$mandateAcceptedDate = '2020-01-01';

		/** @var WC_Order&MockObject $wcOrderMock */
		$wcOrderMock = $this->createMock( WC_Order::class);

		$wcOrderMock->expects($this->at(0))
			->method('update_meta_data')
			->with(SubscriptionCrud::MANDATE_ACCEPTED_DATE_FIELD, $mandateAcceptedDate);

		$wcOrderMock->expects($this->at(1))
			->method('update_meta_data')
			->with(SubscriptionCrud::ECURRING_SUBSCRIPTION_ID_FIELD, $subscriptionId);

		$wcOrderMock->expects($this->once())
			->method('save');

		when('date')->justReturn($mandateAcceptedDate);
		when('__')->returnArg();

		$wcOrderMock->expects($this->once())
			->method('add_order_note')
			->with($this->stringContains($subscriptionId));

		$sut = new SubscriptionCrud();
		$sut->saveSubscription($subscriptionData, $wcOrderMock);
	}

	/**
	 * Subscription data example is taken from the eCurring documentation.
	 *
	 * @see https://docs.ecurring.com/subscriptions/get
	 */
	public function subscriptionDataProvider() {
		$subscriptionId = '1';

		$mandateAcceptedDate = '2020-01-01';

		return [
			[
				[
					"links" => [
						"self" => "https=>//api.ecurring.com/subscriptions/1"
					],
					"data"  => [
						"type"          => "subscription",
						"id"            => $subscriptionId,
						"links"         => [
							"self" => "https=>//api.ecurring.com/subscriptions/1"
						],
						"attributes"    => [
							"mandate_code"             => "ECUR-1",
							"mandate_accepted"         => true,
							"mandate_accepted_date"    => $mandateAcceptedDate,
							"start_date"               => "2017-21-11T22=>11=>57+01=>00",
							"status"                   => "active",
							"cancel_date"              => null,
							"resume_date"              => null,
							"confirmation_page"        => "https=>//app.ecurring.com/mandate/accept/1/ECUR-1",
							"confirmation_sent"        => false,
							"subscription_webhook_url" => null,
							"transaction_webhook_url"  => null,
							"success_redirect_url"     => null,
							"metadata"                 => [],
							"archived"                 => false,
							"created_at"               => "2017-02-01T11=>21=>09+01=>00",
							"updated_at"               => "2017-02-11T00=>00=>00+01=>00"
						],
						"relationships" => [
							"subscription-plan" => [
								"data" => [
									"type" => "subscription-plan",
									"id"   => "1"
								]
							],
							"customer"          => [
								"data" => [
									"type" => "customer",
									"id"   => "1"
								]
							],
							"transactions"      => [
								"links" => [
									"related" => "https=>//api.ecurring.com/subscriptions/1/transactions"
								],
								"data"  => [
									[
										"type" => "transaction",
										"id"   => "02f3c67b-1e1a-4692-8826-14f17f9b2c61"
									]
								]
							]
						]
					]
				],
				$subscriptionId
			]
		];
	}
}
