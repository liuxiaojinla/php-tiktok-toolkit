<?php

namespace Xin\TiktokToolkit\Contracts;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xin\TiktokToolkit\Contracts\AccessToken as AccessTokenInterface;

interface AccessTokenAwareHttpClient extends HttpClientInterface
{
    /**
     * @param AccessTokenInterface $accessToken
     * @return $this
     */
    public function withAccessToken(AccessTokenInterface $accessToken);
}
