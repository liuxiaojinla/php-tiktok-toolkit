<?php

namespace Xin\TiktokToolkit;

use Xin\TiktokToolkit\Support\Str;

class Utils
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $url
     * @param array<string> $jsApiList
     * @param array<string> $openTagList
     * @param bool $debug
     * @return array<string, mixed>
     */
    public function buildJsSdkConfig(
        string $url,
        array  $jsApiList = [],
        array  $openTagList = [],
        bool   $debug = false
    )
    {
        return array_merge(
            compact('jsApiList', 'openTagList', 'debug'),
            $this->app->getTicket()->configSignature($url, Str::random(), time())
        );
    }
}
