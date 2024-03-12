<?php

namespace Xin\TiktokToolkit\HttpClient;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

class ScopingHttpClient implements HttpClientInterface, LoggerAwareInterface, ResetInterface
{
    use HttpClientTrait;

    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var array
     */
    private $defaultOptionsByRegexp;

    /**
     * @param HttpClientInterface $client
     * @param array $defaultOptionsByRegexp
     */
    public function __construct(HttpClientInterface $client, array $defaultOptionsByRegexp)
    {
        $this->client = $client;
        $this->defaultOptionsByRegexp = $defaultOptionsByRegexp;
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        foreach ($this->defaultOptionsByRegexp as $regexp => $defaultOptions) {
            if (preg_match($regexp, $url)) {
                $options = self::mergeDefaultOptions($options, $defaultOptions, true);
                break;
            }
        }

        return $this->client->request($method, $url, $options);
    }

    /**
     * @inheritDoc
     */
    public function stream($responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * @return void
     */
    public function reset()
    {
        if ($this->client instanceof ResetInterface) {
            $this->client->reset();
        }
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }
}
