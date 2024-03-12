<?php

namespace Xin\TiktokToolkit\Contracts;

interface JsApiTicket
{
    public function getTicket();

    /**
     * @return array<string,mixed>
     */
    public function configSignature(string $url, string $nonce, int $timestamp);
}
