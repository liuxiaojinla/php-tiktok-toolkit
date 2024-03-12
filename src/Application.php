<?php

namespace Xin\TiktokToolkit;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Throwable;
use Xin\TiktokToolkit\Contracts\AccessToken;
use Xin\TiktokToolkit\Contracts\Account as AccountInterface;
use Xin\TiktokToolkit\Contracts\Application as ApplicationInterface;
use Xin\TiktokToolkit\Contracts\Config as ConfigInterface;
use Xin\TiktokToolkit\Contracts\OAuth as OAuthInterface;
use Xin\TiktokToolkit\Contracts\Server as ServerInterface;
use Xin\TiktokToolkit\Contracts\VerifyTicket as VerifyTicketInterface;
use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;
use Xin\TiktokToolkit\HttpClient\AccessTokenAwareClient;
use Xin\TiktokToolkit\HttpClient\AccessTokenExpiredRetryStrategy;
use Xin\TiktokToolkit\HttpClient\RequestUtil;
use Xin\TiktokToolkit\HttpClient\Response;
use Xin\TiktokToolkit\Traits\InteractWithCache;
use Xin\TiktokToolkit\Traits\InteractWithClient;
use Xin\TiktokToolkit\Traits\InteractWithConfig;
use Xin\TiktokToolkit\Traits\InteractWithHttpClient;
use Xin\TiktokToolkit\Traits\InteractWithServerRequest;

class Application implements ApplicationInterface
{
    use InteractWithConfig;
    use InteractWithCache;
    use InteractWithClient;
    use InteractWithHttpClient;
    use InteractWithServerRequest;

    /**
     * @var AccessToken
     */
    protected $clientAccessToken;

    /**
     * @var Encryptor|null
     */
    protected $encryptor = null;

    /**
     * @var Server|null
     */
    protected $server = null;

    /**
     * @var VerifyTicketInterface|null
     */
    protected $verifyTicket = null;

    /**
     * @var AccountInterface|null
     */
    protected $account = null;

    /**
     * @var OAuthInterface|null
     */
    protected $oauth = null;

    /**
     * @param array<string,mixed>|ConfigInterface $config
     * @throws InvalidArgumentException
     */
    public function __construct($config)
    {
        $this->config = Config::form($config);
    }

    /**
     * @return AccountInterface
     */
    public function getAccount(): AccountInterface
    {
        if (!$this->account) {
            $this->account = new Account(
                (string)$this->config->get('client_key'), /** @phpstan-ignore-line */
                (string)$this->config->get('client_secret'), /** @phpstan-ignore-line */
                (string)$this->config->get('token'), /** @phpstan-ignore-line */
                (string)$this->config->get('aes_key'),/** @phpstan-ignore-line */
            );
        }

        return $this->account;
    }

    /**
     * @param AccountInterface $account
     * @return $this
     */
    public function setAccount(AccountInterface $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return VerifyTicketInterface
     */
    public function getVerifyTicket(): VerifyTicketInterface
    {
        if (!$this->verifyTicket) {
            $this->verifyTicket = new VerifyTicket(
                $this->getAccount()->getClientKey(),
                null,
                $this->getCache(),
            );
        }

        return $this->verifyTicket;
    }

    /**
     * @param VerifyTicketInterface $verifyTicket
     * @return $this
     */
    public function setVerifyTicket(VerifyTicketInterface $verifyTicket)
    {
        $this->verifyTicket = $verifyTicket;

        return $this;
    }

    /**
     * @return Encryptor
     */
    public function getEncryptor(): Encryptor
    {
        if (!$this->encryptor) {
            $this->encryptor = new Encryptor(
                $this->getAccount()->getClientKey(),
                $this->getAccount()->getToken(),
                $this->getAccount()->getAesKey(),
                $this->getAccount()->getClientKey(),
            );
        }

        return $this->encryptor;
    }

    /**
     * @param Encryptor $encryptor
     * @return $this
     */
    public function setEncryptor(Encryptor $encryptor)
    {
        $this->encryptor = $encryptor;

        return $this;
    }

    /**
     * @return Server|ServerInterface
     */
    public function getServer(): ServerInterface
    {
        if (!$this->server) {
            $this->server = new Server(
                $this->getEncryptor(),
                $this->getRequest()
            );
        }

        return $this->server;
    }

    /**
     * @param ServerInterface $server
     * @return $this
     */
    public function setServer(ServerInterface $server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return Server|ServerInterface
     * @throws Throwable
     */
    public function getOAuth(): OAuthInterface
    {
        if (!$this->oauth) {
            $this->oauth = new OAuth(
                $this->getAccount()->getClientKey(),
                $this->getAccount()->getClientSecret(),
                $this->getHttpClient()
            );
        }

        return $this->oauth;
    }

    /**
     * @param OAuthInterface|null $oauth
     * @return $this
     */
    public function setOAuth(OAuthInterface $oauth)
    {
        $this->oauth = $oauth;

        return $this;
    }

    /**
     * 获取授权者实例
     * @param string $authorizerOpenId
     * @param string $accessToken
     * @return Authorizer
     */
    public function authorizer(string $authorizerOpenId, string $accessToken)
    {
        $httpClient = $this->createAuthorizerClient($authorizerOpenId, $accessToken);

        return new Authorizer($httpClient);
    }

    /**
     * @return AccessToken
     */
    public function getClientAccessToken(): AccessToken
    {
        if (!$this->clientAccessToken) {
            $this->clientAccessToken = new ClientAccessToken(
                $this->getAccount()->getClientKey(),
                $this->getAccount()->getClientSecret(),
                $this->getCache(),
                $this->getHttpClient()
            );
        }

        return $this->clientAccessToken;
    }

    /**
     * @param AccessToken $clientAccessToken
     */
    public function setClientAccessToken(AccessToken $clientAccessToken)
    {
        $this->clientAccessToken = $clientAccessToken;
    }

    /**
     * @return AccessTokenAwareClient
     */
    public function createClient(): AccessTokenAwareClient
    {
        $httpClient = $this->getHttpClient();

        if ($this->config->get('http.retry', false)) {
            $httpClient = new RetryableHttpClient(
                $httpClient,
                $this->getRetryStrategy(),
                (int)$this->config->get('http.max_retries', 2)
            );
        }

        return (new AccessTokenAwareClient(
            $httpClient,
            $this->getClientAccessToken(),
            function (Response $response) {
                return (bool)($response->toArray()['errcode'] ?? 0);
            },
            (bool)$this->config->get('http.throw', true),
        ))->setPresets($this->config->all());
    }

    /**
     * @param string $authorizerOpenId
     * @param string $accessToken
     * @return AccessTokenAwareClient
     */
    public function createAuthorizerClient(string $authorizerOpenId, string $accessToken): AccessTokenAwareClient
    {
        $httpClient = $this->getHttpClient();

        if ($this->config->get('http.retry', false)) {
            $httpClient = new RetryableHttpClient(
                $httpClient,
                $this->getRetryStrategy(),
                (int)$this->config->get('http.max_retries', 2)
            );
        }

        $authorizerAccessToken = new AuthorizerAccessToken(
            $this->getAccount()->getClientKey(),
            $authorizerOpenId,
            $accessToken
        );

        return (new AccessTokenAwareClient(
            $httpClient,
            $authorizerAccessToken,
            function (Response $response) {
                return (bool)($response->toArray()['errcode'] ?? 0);
            },
            (bool)$this->config->get('http.throw', true),
        ))->setPresets($this->config->all());
    }

    /**
     * @return AccessTokenExpiredRetryStrategy
     */
    public function getRetryStrategy(): AccessTokenExpiredRetryStrategy
    {
        $retryConfig = RequestUtil::mergeDefaultRetryOptions((array)$this->config->get('http.retry', []));

        return (new AccessTokenExpiredRetryStrategy($retryConfig))
            ->decideUsing(function (AsyncContext $context, ?string $responseContent) {
                return !empty($responseContent)
                    && str_contains($responseContent, '42001')
                    && str_contains($responseContent, 'access_token expired');
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHttpClientDefaultOptions()
    {
        return array_merge(
            [
                'base_uri' => 'https://open.tiktokapis.com/',
            ],
            (array)$this->config->get('http', [])
        );
    }
}
