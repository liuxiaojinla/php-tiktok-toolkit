<?php

namespace Xin\TiktokToolkit\Traits;

use Mockery\Mock;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

trait MockableHttpClient
{
    public static function createMockClient(MockHttpClient $mockHttpClient): HttpClientInterface
    {
        return new self($mockHttpClient);
    }

    /**
     * @param array<string,mixed> $headers
     */
    public static function mock(
        string $response = '',
        ?int   $status = 200,
        array  $headers = [],
        string $baseUri = 'https://example.com'
    ): object
    {
        $mockResponse = new MockResponse(
            $response,
            array_merge([
                'http_code' => $status,
                'content_type' => 'application/json',
            ], $headers)
        );

        $client = self::createMockClient(new MockHttpClient($mockResponse, $baseUri));

        // @phpstan-ignore-next-line
        return new class($client, $mockResponse) {
            use DecoratorTrait;

            /**
             * @var MockResponse
             */
            public $mockResponse;

            /**
             * @param Mock|HttpClientInterface $client
             * @param MockResponse $mockResponse
             */
            public function __construct($client, MockResponse $mockResponse)
            {
                $this->client = $client;
                $this->mockResponse = $mockResponse;
            }

            /**
             * @param array<string,mixed> $arguments
             */
            public function __call(string $name, array $arguments)
            {
                return $this->client->$name(...$arguments);
            }

            /**
             * @return string
             */
            public function getRequestMethod()
            {
                return $this->mockResponse->getRequestMethod();
            }

            /**
             * @return string
             */
            public function getRequestUrl()
            {
                return $this->mockResponse->getRequestUrl();
            }

            /**
             * @return array<string, mixed>
             */
            public function getRequestOptions()
            {
                return $this->mockResponse->getRequestOptions();
            }
        };
    }
}
