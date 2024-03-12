<?php

namespace Xin\TiktokToolkit;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xin\TiktokToolkit\Contracts\OAuth as OAuthInterface;
use Xin\TiktokToolkit\Exceptions\HttpException;

class OAuth implements OAuthInterface
{
    /**
     * @var string
     */
    protected $clientKey;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @param string $clientKey
     * @param string $clientSecret
     * @param HttpClientInterface $httpClient
     */
    public function __construct(string $clientKey, string $clientSecret, HttpClientInterface $httpClient)
    {
        $this->clientKey = $clientKey;
        $this->clientSecret = $clientSecret;
        $this->httpClient = $httpClient;
    }

    /**
     * @inheritDoc
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws HttpException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getAuthorizerToken(string $authorizationCode, string $redirectUri, array $optional = [])
    {
        $formParams = array_merge(
            $optional, [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
        $response = $this->httpClient->request(
            'POST',
            'v2/oauth/token/',
            [
                'body' => $formParams,
            ]
        )->toArray(false);

        if (empty($response['access_token'])) {
            throw new HttpException('Failed to get authorization_info: ' . json_encode(
                    $response,
                    JSON_UNESCAPED_UNICODE
                ));
        }

        return $response;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws HttpException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function refreshAuthorizerToken(string $authorizerRefreshToken, array $optional = [])
    {
        $formParams = array_merge(
            $optional, [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $authorizerRefreshToken,
        ]);
        $response = $this->httpClient->request(
            'POST',
            'v2/oauth/token/',
            [
                'body' => $formParams,
            ]
        )->toArray(false);

        if (empty($response['access_token'])) {
            throw new HttpException('Failed to get authorizer_access_token: ' . json_encode(
                    $response,
                    JSON_UNESCAPED_UNICODE
                ));
        }

        return $response;
    }

    /**
     * @param array $scope
     * @param string $callbackUrl
     * @param array $optional
     * @return string
     */
    public function createPreAuthorizationUrl(array $scope, string $callbackUrl, array $optional = []): string
    {
        $queries = array_merge(
            $optional,
            [
                'client_key' => $this->clientKey,
                'redirect_uri' => $callbackUrl,
                'response_type' => 'code',
                'scope' => implode(',', $scope),
            ]
        );

        return 'https://www.tiktok.com/v2/auth/authorize/?' . http_build_query($queries);
    }
}
