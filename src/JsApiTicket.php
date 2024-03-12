<?php

namespace Xin\TiktokToolkit;

use JetBrains\PhpStorm\ArrayShape;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xin\TiktokToolkit\Contracts\RefreshableJsApiTicket as RefreshableJsApiTicketInterface;
use Xin\TiktokToolkit\Exceptions\HttpException;

class JsApiTicket implements RefreshableJsApiTicketInterface
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
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws HttpException
     * @throws ServerExceptionInterface
     */
    public function getTicket(): string
    {
        $key = $this->getKey();
        $ticket = $this->cache->get($key);

        if ($ticket && is_string($ticket)) {
            return $ticket;
        }

        return $this->refreshTicket();
    }

    public function refreshTicket(): string
    {
        $response = $this->httpClient->request('GET', '/cgi-bin/ticket/getticket', ['query' => ['type' => 'jsapi']])
            ->toArray(false);

        if (empty($response['ticket'])) {
            throw new HttpException('Failed to get jssdk ticket: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        $this->cache->set($this->getKey(), $response['ticket'], intval($response['expires_in']));

        return $response['ticket'];
    }

    /**
     * @return array<string,mixed>
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[ArrayShape([
        'url' => 'string',
        'nonceStr' => 'string',
        'timestamp' => 'int',
        'appId' => 'string',
        'signature' => 'string',
    ])]
    public function configSignature(string $url, string $nonce, int $timestamp)
    {
        return [
            'url' => $url,
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'appId' => $this->clientKey,
            'signature' => sha1(sprintf(
                'jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s',
                $this->getTicket(),
                $nonce,
                $timestamp,
                $url
            )),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->key ?? $this->key = sprintf('official_account.jsapi_ticket.%s', $this->clientKey);
    }
}
