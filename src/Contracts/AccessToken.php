<?php

namespace Xin\TiktokToolkit\Contracts;

interface AccessToken
{
    /**
     * @return string
     */
    public function get();

    /**
     * @return array<string,string>
     */
    public function toQuery();
}
