<?php

namespace Xin\TiktokToolkit\Contracts;

interface Account
{
    /**
     * @return string
     */
    public function getClientKey(): string;

    /**
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * @return string
     */
    public function getToken(): string;

    /**
     * @return string
     */
    public function getAesKey(): string;
}
