<?php

namespace Xin\TiktokToolkit\Providers;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Xin\TiktokToolkit\Authorizer;
use Xin\TiktokToolkit\Contracts\AccessTokenAwareHttpClient;

class Research
{
    /**
     * @var AccessTokenAwareHttpClient
     */
    protected $httpClient;

    /**
     * @param AccessTokenAwareHttpClient $httpClient
     */
    public function __construct(AccessTokenAwareHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * 获取最近视频列表
     * @param array $params
     * @param string|null $fields
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example
     * {
     * "query": {
     * "and": [
     * {
     * "operation": "IN",
     * "field_name": "region_code",
     * "field_values": ["JP", "US"]
     * },
     * {
     * "operation":"EQ",
     * "field_name":"hashtag_name",
     * "field_values":["animal"]
     * }
     * ],
     * "not": [
     * {
     * "operation": "EQ",
     * "field_name": "video_length",
     * "field_values": ["SHORT"]
     * }
     * ]
     * },
     * "max_count": 100,
     * "cursor": 0,
     * "start_date": "20230101",
     * "end_date": "20230115"
     * }
     * @link https://developers.tiktok.com/doc/research-api-specs-query-videos/
     */
    public function videoLists(array $params = [], string $fields = null)
    {
        if (empty($fields)) {
            $fields = Video::defaultQueryFields();
        }

        $params = array_merge([
            'max_count' => 20,
        ], $params);

        return $this->httpClient->request(
            'POST',
            'v2/research/video/comment/list/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => $params,
            ]
        )->toArray(false);
    }

    /**
     * 获取用户信息
     * @param string $username
     * @param string|null $fields
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example {"username": "joe123456"}
     * @link https://developers.tiktok.com/doc/research-api-specs-query-user-info/
     */
    public function userInfo(string $username, string $fields = null)
    {
        if (empty($fields)) {
            $fields = Authorizer::getUserInfoDefaultFields();
        }

        return $this->httpClient->request(
            'POST',
            'v2/research/user/info/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => [
                    "username" => $username,
                ],
            ]
        )->toArray(false);
    }

    /**
     * 获取视频评论列表
     * @param array $params
     * @param string|null $fields
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example {
     * "video_id": 7178997441201524010,
     * "max_count": 50,
     * "cursor": 150
     * }
     * @link https://developers.tiktok.com/doc/research-api-specs-query-video-comments/
     */
    public function videoCommentList(array $params = [], string $fields = null)
    {
        if (empty($fields)) {
            $fields = Authorizer::getUserInfoDefaultFields();
        }

        return $this->httpClient->request(
            'POST',
            'v2/research/video/comment/list/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => $params,
            ]
        )->toArray(false);

    }

    /**
     * 获取视频评论默认字段
     * @return string
     */
    public static function getVideoCommentFields()
    {
        return 'id, video_id, text, like_count, reply_count, parent_comment_id, create_time';
    }

    /**
     * 获取用户点赞的视频列表
     * @param array $params
     * @param string|null $fields
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example {
     * "username": "test_username",
     * "max_count": 1,
     * "cursor" : 1706457540000,
     * }
     * @link https://developers.tiktok.com/doc/research-api-specs-query-user-liked-videos/
     */
    public function getUserLikedVideoList(array $params = [], string $fields = null)
    {
        if (empty($fields)) {
            $fields = 'id,create_time,username,region_code,video_description,music_id,like_count,comment_count,share_count,view_count,hashtag_names';
        }

        return $this->httpClient->request(
            'POST',
            'v2/research/user/liked_videos/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => $params,
            ]
        )->toArray(false);
    }

    /**
     * 获取用户固定的视频列表
     * @param array $params
     * @param string|null $fields
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example {
     * "username": "test_username",
     * "max_count": 1,
     * "cursor" : 1706457540000,
     * }
     * @link https://developers.tiktok.com/doc/research-api-specs-query-user-liked-videos/
     */
    public function getUserPinnedVideoList(array $params = [], string $fields = null)
    {
        if (empty($fields)) {
            $fields = 'id,create_time,username,region_code,video_description,music_id,like_count,comment_count,share_count,view_count,hashtag_names';
        }

        return $this->httpClient->request(
            'POST',
            'v2/research/user/pinned_videos/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => $params,
            ]
        )->toArray(false);
    }

    /**
     * 获取关注当前用户的列表
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example {
     * "username": "test_username",
     * "max_count": 1,
     * "cursor" : 1706457540000,
     * }
     * @link https://developers.tiktok.com/doc/research-api-specs-query-user-followers/
     */
    public function getUserFollowers(array $params = [])
    {
        return $this->httpClient->request(
            'POST',
            'v2/research/use/followers/',
            [
                'json' => $params,
            ]
        )->toArray(false);
    }

    /**
     * 获取当前用户关注的列表
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example {
     * "username": "test_username",
     * "max_count": 1,
     * "cursor" : 1706457540000,
     * }
     * @link https://developers.tiktok.com/doc/research-api-specs-query-user-following/
     */
    public function getUserFollowing(array $params = [])
    {
        return $this->httpClient->request(
            'POST',
            'v2/research/user/following/',
            [
                'json' => $params,
            ]
        )->toArray(false);
    }

    /**
     * 获取当前用户关注的列表
     * @param array $params
     * @param string|null $fields
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @example {
     * "username": "test_username",
     * "max_count": 1,
     * "cursor" : 1706457540000,
     * }
     * @link https://developers.tiktok.com/doc/research-api-specs-query-user-following/
     */
    public function getUserRepostedVideos(array $params = [], string $fields = null)
    {
        if (empty($fields)) {
            $fields = 'id,create_time,username,region_code,video_description,music_id,like_count,comment_count,share_count,view_count,hashtag_names';
        }

        return $this->httpClient->request(
            'POST',
            'v2/research/user/reposted_videos/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => $params,
            ]
        )->toArray(false);
    }
}
