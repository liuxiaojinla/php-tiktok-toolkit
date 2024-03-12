<?php

namespace Xin\TiktokToolkit\HttpClient;

use Closure;
use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Xin\TiktokToolkit\Contracts\AccessToken as AccessTokenInterface;
use Xin\TiktokToolkit\Contracts\AccessTokenAwareHttpClient as AccessTokenAwareHttpClientInterface;
use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;
use Xin\TiktokToolkit\Traits\MockableHttpClient;

/**
 * Class AccessTokenAwareClient.
 *
 *
 * @method HttpClientInterface withAppIdAs(string $name = null) 自定义 app_id 参数名
 * @method HttpClientInterface withAppId(string $value = null)
 */
class AccessTokenAwareClient implements AccessTokenAwareHttpClientInterface
{
    use AsyncDecoratorTrait;
    use HttpClientMethods;
    use MockableHttpClient;
    use RequestWithPresets;
    use RetryableClient;

    /**
     * @var AccessTokenInterface|null
     */
    protected $accessToken = null;

    /**
     * @var Closure|null
     */
    protected $failureJudge;

    /**
     * @var bool
     */
    protected $throw = true;

    /**
     * @param HttpClientInterface|null $client
     * @param AccessTokenInterface|null $accessToken
     * @param Closure|null $failureJudge
     * @param bool $throw
     */
    public function __construct(
        ?HttpClientInterface  $client = null,
        ?AccessTokenInterface $accessToken = null,
        ?Closure              $failureJudge = null,
        bool                  $throw = true
    )
    {
        $this->client = $client ?? HttpClient::create();
        $this->accessToken = $accessToken;
        $this->failureJudge = $failureJudge;
        $this->throw = $throw;
    }

    /**
     * @param AccessTokenInterface $accessToken
     * @return $this|AccessTokenAwareClient
     */
    public function withAccessToken(AccessTokenInterface $accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws TransportExceptionInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->accessToken) {
            if (empty($options['headers'])) {
                $options['headers'] = [];
            }
            $options['headers']['Authorization'] = "Bearer " . $this->accessToken->get();
//            $options['query'] = array_merge((array)($options['query'] ?? []), $this->accessToken->toQuery());
        }

        $options = RequestUtil::formatBody($this->mergeThenResetPrepends($options));

        return new Response(
            $this->client->request($method, ltrim($url, '/'), $options),
            $this->failureJudge,
            $this->throw
        );
    }

    /**
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $arguments)
    {
        if (str_starts_with($name, 'with')) {
            return $this->handleMagicWithCall($name, $arguments[0] ?? null);
        }

        return $this->client->$name(...$arguments);
    }

    public static function createMockClient(MockHttpClient $mockHttpClient): HttpClientInterface
    {
        return new self($mockHttpClient);
    }
}
