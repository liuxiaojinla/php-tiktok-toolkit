<?php

namespace Xin\TiktokToolkit;

use Xin\TiktokToolkit\Contracts\AccessTokenAwareHttpClient;
use Xin\TiktokToolkit\Providers\Research;
use Xin\TiktokToolkit\Providers\Video;

class Authorizer
{
    /**
     * @var AccessTokenAwareHttpClient
     */
    protected $httpClient;

    /**
     * @var Video
     */
    protected $videoManager;

    /**
     * @var Research
     */
    protected $research;

    /**
     * @param AccessTokenAwareHttpClient $httpClient
     */
    public function __construct(AccessTokenAwareHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * 获取视频管理实例
     * @param string $authorizerOpenId
     * @param string $accessToken
     * @return Video
     */
    public function videoManager(): Video
    {
        if (!$this->videoManager) {
            $this->videoManager = new Video(
                $this->httpClient
            );
        }

        return $this->videoManager;
    }

    /**
     * 获取数据探究实例
     * @param string $authorizerOpenId
     * @param string $accessToken
     * @return Research
     */
    public function research(): Research
    {
        if (!$this->research) {
            $this->research = new Video(
                $this->httpClient
            );
        }

        return $this->research;
    }
}
