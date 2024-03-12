<?php

namespace Xin\TiktokToolkit\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xin\TiktokToolkit\Encryptor;
use Xin\TiktokToolkit\HttpClient\AccessTokenAwareClient;

interface Application
{
    /**
     * @return Account
     */
    public function getAccount(): Account;

    /**
     * @return Encryptor
     */
    public function getEncryptor(): Encryptor;

    /**
     * @return Server
     */
    public function getServer(): Server;

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface;

    /**
     * @return AccessTokenAwareClient
     */
    public function getClient(): AccessTokenAwareClient;

    /**
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface;

    /**
     * @param string $authorizerOpenId
     * @param string $accessToken
     * @return AccessTokenAwareClient
     */
    public function createAuthorizerClient(string $authorizerOpenId, string $accessToken): AccessTokenAwareClient;

    /**
     * @return Config
     */
    public function getConfig(): Config;

    /**
     * @return AccessToken
     */
    public function getClientAccessToken(): AccessToken;

    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface;

    /**
     * @return OAuth
     */
    public function getOAuth(): OAuth;


}
