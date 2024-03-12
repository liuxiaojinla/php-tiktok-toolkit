<?php

namespace Xin\TiktokToolkit;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xin\TiktokToolkit\Contracts\AccessToken;
use Xin\TiktokToolkit\Exceptions\HttpException;

class ClientAccessToken implements AccessToken
{
    /**
     * @var string
     */
    const CACHE_KEY_PREFIX = 'client';

    /**
     * @var string
     */
    protected $clientKey;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var string|null
     */
    protected $key;

    /**
     * @param string $clientKey
     * @param string $clientSecret
     * @param CacheInterface|null $cache
     * @param HttpClientInterface|null $httpClient
     * @param string|null $key
     */
    public function __construct(
        string               $clientKey,
        string               $clientSecret,
        ?CacheInterface      $cache = null,
        ?HttpClientInterface $httpClient = null,
        string               $key = null
    )
    {
        $this->clientKey = $clientKey;
        $this->clientSecret = $clientSecret;
        $this->httpClient = $httpClient ?? HttpClient::create(['base_uri' => 'https://open.tiktokapis.com/']);
        $this->cache = $cache ?? new Psr16Cache(new FilesystemAdapter('tiktok', 1500));
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        if (!$this->key) {
            $this->key = sprintf('%s.access_token.%s.%s', static::CACHE_KEY_PREFIX, $this->clientKey, $this->clientSecret);
        }

        return $this->key;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey(string $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        $token = $this->getInfo();

        return $token['access_token'];
    }

    /**
     * @return array<string,string>
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getInfo()
    {
        $token = $this->cache->get($this->getKey());

        if (!$token || !is_array($token)) {
            $response = $this->requestAccessToken();
            $this->cache->set($this->getKey(), $response, intval($response['expires_in']));
        }

        return $token;
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function forget()
    {
        $this->cache->delete($this->getKey());
    }

    /**
     * @return array<string,mixed>
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function requestAccessToken()
    {
        $response = $this->httpClient->request(
            'POST',
            '/v2/oauth/token/',
            [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_key' => $this->clientKey,
                    'client_secret' => $this->clientSecret,
                ],
            ]
        )->toArray(false);

        if (empty($response['access_token'])) {
            throw new HttpException('Failed to get access_token: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function toQuery()
    {
        return ['access_token' => $this->get()];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}
