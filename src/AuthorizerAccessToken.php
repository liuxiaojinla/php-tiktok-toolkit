<?php

namespace Xin\TiktokToolkit;


use Xin\TiktokToolkit\Contracts\AccessToken;

class AuthorizerAccessToken implements AccessToken
{
    /**
     * @var string
     */
    protected $clientKey;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var mixed
     */
    protected $authorizerOpenId;

    /**
     * @param string $clientKey
     * @param string $authorizerOpenId
     * @param string $accessToken
     */
    public function __construct(string $clientKey, string $authorizerOpenId, string $accessToken)
    {
        $this->clientKey = $clientKey;
        $this->authorizerOpenId = $authorizerOpenId;
        $this->accessToken = $accessToken;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->accessToken;
    }

    public function getClientKey()
    {
        return $this->clientKey;
    }

    public function __toString()
    {
        return $this->accessToken;
    }

    /**
     * @return array<string, string>
     */
    public function toQuery()
    {
        return ['access_token' => $this->get()];
    }
}
