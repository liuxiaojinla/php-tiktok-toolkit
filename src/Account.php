<?php

namespace Xin\TiktokToolkit;

use Xin\TiktokToolkit\Contracts\Account as AccountInterface;

class Account implements AccountInterface
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
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $aesKey;

    /**
     * @param string $clientKey
     * @param string $clientSecret
     * @param string $token
     * @param string $aesKey
     */
    public function __construct(
        string $clientKey,
        string $clientSecret,
        string $token,
        string $aesKey
    )
    {
        $this->clientKey = $clientKey;
        $this->clientSecret = $clientSecret;
        $this->token = $token;
        $this->aesKey = $aesKey;
    }

    /**
     * @inheritDoc
     */
    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @inheritDoc
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @inheritDoc
     */
    public function getAesKey(): string
    {
        return $this->aesKey;
    }
}
