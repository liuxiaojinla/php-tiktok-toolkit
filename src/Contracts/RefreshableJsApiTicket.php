<?php

namespace Xin\TiktokToolkit\Contracts;

interface RefreshableJsApiTicket extends JsApiTicket
{
    /**
     * @return string
     */
    public function refreshTicket(): string;
}
