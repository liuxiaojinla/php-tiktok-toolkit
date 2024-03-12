<?php

namespace Xin\TiktokToolkit\Providers;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Xin\TiktokToolkit\Contracts\AccessTokenAwareHttpClient;
use Xin\TiktokToolkit\Exceptions\HttpException;
use Xin\TiktokToolkit\Support\Arr;

class Video
{
    const UPLOAD_CHUNK_SIZE = 1024 * 1024 * 10;

    const MIN_CHUNK_SIZE = 1024 * 1024 * 5;

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
     * 查询创作者信息
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function queryCreatorInfo()
    {
        return $this->httpClient->request(
            'POST',
            'v2/post/publish/creator_info/query/'
        )->toArray(false);
    }

    /**
     * 初始化发布
     * @param array $data
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function init(array $data)
    {
        return $this->httpClient->request(
            'POST',
            'v2/post/publish/video/init/',
            [
                'json' => $data,
            ]
        )->toArray(false);
    }

    /**
     * 查询视频上传的状态
     * @param string $publishId
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function queryPublishStatus(string $publishId)
    {
        return $this->httpClient->request(
            'POST',
            'v2/post/publish/status/fetch/',
            [
                'json' => [
                    'publish_id' => $publishId,
                ],
            ]
        )->toArray(false);
    }

    /**
     * 直接发布本地视频文件
     * @param string $filePath
     * @param array $postInfo
     * @return string
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws HttpException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function publishUsingLocalFile(string $filePath, array $postInfo)
    {
        $fileSize = @filesize($filePath);

        $uploadChunkSize = $this->getUploadChunkSize();
        $uploadChunkSize = min($uploadChunkSize, $fileSize);
        $chunkCount = (int)floor($fileSize / $uploadChunkSize);
        if ($chunkCount <= 1) {
            $uploadChunkSize = $fileSize;
        }

        $response = $this->init([
            "post_info" => $postInfo,
            "source_info" => [
                "source" => "FILE_UPLOAD",
                "video_size" => $fileSize,
                "chunk_size" => $uploadChunkSize,
                "total_chunk_count" => $chunkCount,
            ],
        ]);

        return $this->completeUploadFile($response, $filePath, $fileSize, $uploadChunkSize, $chunkCount);
    }

    /**
     * 初始化上传到草稿箱
     * @param array $data
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function initInbox(array $data)
    {
        return $this->httpClient->request(
            'POST',
            'v2/post/publish/inbox/video/init/',
            [
                'json' => $data,
            ]
        )->toArray(false);
    }

    /**
     * 发布视频到草稿箱
     * @param string $filePath
     * @return string
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws HttpException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function publishInboxUsingLocalFile(string $filePath)
    {
        $fileSize = @filesize($filePath);

        $uploadChunkSize = $this->getUploadChunkSize();
        $uploadChunkSize = min($uploadChunkSize, $fileSize);
        $chunkCount = (int)floor($fileSize / $uploadChunkSize);
        if ($chunkCount <= 1) {
            $uploadChunkSize = $fileSize;
        }

        $response = $this->initInbox([
            "source_info" => [
                "source" => "FILE_UPLOAD",
                "video_size" => $fileSize,
                "chunk_size" => $uploadChunkSize,
                "total_chunk_count" => $chunkCount,
            ],
        ]);

        return $this->completeUploadFile($response, $filePath, $fileSize, $uploadChunkSize, $chunkCount);
    }

    /**
     * 完成上传文件
     * @param array $response
     * @param string $filePath
     * @param int $fileSize
     * @param int $uploadChunkSize
     * @param int $chunkCount
     * @return string
     * @throws HttpException
     * @throws TransportExceptionInterface
     */
    protected function completeUploadFile(
        array  $response,
        string $filePath,
        int    $fileSize,
        int    $uploadChunkSize,
        int    $chunkCount
    )
    {
        if (empty($response['data'])) {
            throw new HttpException('Failed to upload file: ' . json_encode(
                    $response,
                    JSON_UNESCAPED_UNICODE
                ));
        }

        $uploadUrl = Arr::get($response, 'data.upload_url');
        $publishId = Arr::get($response, 'data.publish_id');
        if (empty($publishId)) {
            throw new HttpException(Arr::get($response, 'error.message'));
        }

        for ($i = 0; $i < $chunkCount; $i++) {
            $fileOffsetStart = $i * $uploadChunkSize;

            if ($i == $chunkCount - 1) {
                $fileOffsetEnd = $fileSize - 1;
            } else {
                $fileOffsetEnd = ($i + 1) * $uploadChunkSize - 1;
            }

            $result = $this->uploadFile($filePath, $fileOffsetStart, $fileOffsetEnd, $fileSize, $uploadUrl);

            if ($result->getStatusCode() < 200 || $result->getStatusCode() >= 300) {
                throw new HttpException('Failed to upload file: ' . ($i + 1) . 'th shard failed,' . $filePath);
            }
        }

        return $publishId;
    }

    /**
     * 文件上传
     * @param string $filePath
     * @param int $fileOffsetStart
     * @param int $fileOffsetEnd
     * @param int $fileSize
     * @param string $uploadUrl
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function uploadFile(string $filePath, int $fileOffsetStart, int $fileOffsetEnd, int $fileSize, string $uploadUrl)
    {
        $currentChunkSize = $fileOffsetEnd - $fileOffsetStart + 1;
        return $this->httpClient->request(
            'PUT',
            $uploadUrl,
            [
                'headers' => [
                    'Content-Range' => "bytes {$fileOffsetStart}-{$fileOffsetEnd}/{$fileSize}",
                    'Content-Type' => 'video/mp4',
                    'Content-Length' => "{$currentChunkSize}",
                ],
                'body' => $this->readFile(
                    $filePath, $fileOffsetStart, $currentChunkSize
                ),
            ]
        );
    }

    /**
     * @param string $filePath
     * @param int $fileSeek
     * @param int $readSize
     * @return false|string|null
     */
    protected function readFile(string $filePath, int $fileSeek, int $readSize)
    {
        $handle = fopen($filePath, 'rb');

        fseek($handle, $fileSeek);

        if (feof($handle)) {
            fclose($handle);
            return null;
        }

        $content = fread($handle, $readSize);
        fclose($handle);

        return $content;
    }

    /**
     * @return int
     */
    public function getUploadChunkSize()
    {
        return self::UPLOAD_CHUNK_SIZE;
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
     */
    public function lists(array $params = [], string $fields = null)
    {
        if (empty($fields)) {
            $fields = static::defaultQueryFields();
        }

        $params = array_merge([
            'max_count' => 20,
        ], $params);

        return $this->httpClient->request(
            'POST',
            'v2/video/list/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => $params,
            ]
        )->toArray(false);
    }

    /**
     * 查询视频列表
     * @param array $params
     * @param string|null $fields
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function search(array $params = [], string $fields = null)
    {
        if (empty($fields)) {
            $fields = static::defaultQueryFields();
        }

        return $this->httpClient->request(
            'POST',
            'v2/video/query/',
            [
                'query' => [
                    'fields' => $fields,
                ],
                'json' => $params,
            ]
        )->toArray(false);
    }

    public static function defaultQueryFields()
    {
        return 'id,title,video_description,duration,cover_image_url';
    }
}
