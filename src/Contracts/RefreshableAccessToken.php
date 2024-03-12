<?php

namespace Xin\TiktokToolkit\Contracts;

interface RefreshableAccessToken extends AccessToken
{
    /**
     * @return string
     */
    public function refresh(): string;
}
