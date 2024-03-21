<?php

namespace Xin\TiktokToolkit;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
            $this->research = new Research(
                $this->httpClient
            );
        }

        return $this->research;
    }

    /**
     * 获取用户信息的默认字段
     * @return string
     */
    public static function getUserInfoDefaultFields()
    {
        return 'display_name,bio_description,avatar_url,is_verified,follower_count,following_count,likes_count,video_count';
    }

    /**
     * 获取当前用户信息
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getInfo($fields = null)
    {
        if (empty($fields)) {
            $fields = static::getUserInfoDefaultFields();
        }

        return $this->httpClient->request(
            'GET',
            'v2/user/info/',
            [
                'query' => [
                    'fields' => $fields,
                ],
            ]
        )->toArray(false);
    }
}
