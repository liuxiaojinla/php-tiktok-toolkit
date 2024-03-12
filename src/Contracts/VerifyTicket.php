<?php

namespace Xin\TiktokToolkit\Contracts;

interface VerifyTicket
{
    /**
     * @return string
     */
    public function getTicket(): string;
}
