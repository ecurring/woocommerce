<?php

namespace Ecurring\WooEcurringTests\Unit\Api;

use Ecurring\WooEcurring\Api\ApiClient;
use Ecurring\WooEcurring\Api\ApiClientException;
use Ecurring\WooEcurringTests\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WP_Error;

use function Brain\Monkey\Functions\expect;

class ApiClientTest extends TestCase
{

    //This trait usage is needed so PhpUnit can detect expect() function as assertion.
    use MockeryPHPUnitIntegration;
    /**
     * @dataProvider apiCallDataProvider
     */
    public function testApiCall(array $requestData, string $apiKey, array $expectedRequestArgs, $testResponse)
    {
        $sut = new ApiClient($apiKey);

        expect('wp_remote_request')
            ->once()
            ->with($requestData['url'], $expectedRequestArgs)
            ->andReturn($testResponse);

        expect('is_wp_error')
            ->once()
            ->andReturnUsing(function ($itemToCheck) {
                return $itemToCheck instanceof WP_Error;
            });

        if ($testResponse instanceof WP_Error) {
            $this->expectException(ApiClientException::class);
            $this->expectExceptionMessage('WP_Error returned for the API request: ' . $testResponse->get_error_message());
        }

        $sut->apiCall($requestData['method'], $requestData['url'], $requestData['data']);
    }

    /**
     * @return array
     */
    public function apiCallDataProvider(): array
    {
        $testResponse = [
            'body' => json_encode(['test' => '123']),
        ];

        $getRequestArgs = [
            'method' => 'GET',
            'url' => 'http://example.com',
            'data' => [],
        ];

        $apiKey = 'sometestapikey123';

        $getRequestExpectedArgs = [
            'method' => $getRequestArgs['method'],
            'headers' =>  [
                'X-Authorization' => $apiKey,
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ],
            'body' => '',
        ];

        $postRequestData = [
            'type' => 'subscription',
            'attributes' => [
                'customer_id' => '12345',
                'subscription_plan_id' => '5678',
                'transaction_webhook_url' => 'myhost.com/test?param=value',
                'confirmation_sent' => true,
                'metadata' => ['source' => 'woocommerce'],
            ],
        ];

        $postRequestArgs = [
            'method' => 'POST',
            'url' => 'http://example.com/postrequests',
            'data' => $postRequestData,
            ];

        $postExpectedArgs = [
            'method' => $postRequestArgs['method'],
            'headers' =>  [
                'X-Authorization' => $apiKey,
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ],
            'body' => json_encode($postRequestData),
        ];

        return [
            [
                $getRequestArgs, $apiKey, $getRequestExpectedArgs, $testResponse,
            ],

            [
                $postRequestArgs, $apiKey, $postExpectedArgs, $testResponse,
            ],
            [
                $getRequestArgs, $apiKey, $getRequestExpectedArgs, $this->createConfiguredMock(
                    WP_Error::class,
                    [
                    'get_error_message' => 'This is the test error message.',
                    'get_error_code' => 12345,
                    ]
                ),
            ],
        ];
    }
}
