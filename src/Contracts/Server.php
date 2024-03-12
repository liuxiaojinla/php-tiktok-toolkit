<?php

namespace Xin\TiktokToolkit\Contracts;

use Psr\Http\Message\ResponseInterface;

interface Server
{
    public function serve(): ResponseInterface;
}
