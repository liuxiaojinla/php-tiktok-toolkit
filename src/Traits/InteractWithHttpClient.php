<?php

namespace Xin\TiktokToolkit\Traits;

use Psr\Log\LoggerAwareInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xin\TiktokToolkit\HttpClient\RequestUtil;
use Xin\TiktokToolkit\HttpClient\ScopingHttpClient;
use Xin\TiktokToolkit\Support\Arr;

trait InteractWithHttpClient
{
    /**
     * @var HttpClientInterface|null
     */
    protected $httpClient = null;

    /**
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface
    {
        if (!$this->httpClient) {
            $this->httpClient = $this->createHttpClient();
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $httpClient
     * @return $this
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        if ($this instanceof LoggerAwareInterface && $httpClient instanceof LoggerAwareInterface
            && property_exists($this, 'logger')
            && $this->logger) {
            $httpClient->setLogger($this->logger);
        }

        return $this;
    }

    /**
     * @return HttpClientInterface
     */
    protected function createHttpClient(): HttpClientInterface
    {
        $options = $this->getHttpClientDefaultOptions();

        $optionsByRegexp = Arr::get($options, 'options_by_regexp', []);
        unset($options['options_by_regexp']);

        $client = HttpClient::create(RequestUtil::formatDefaultOptions($options));

        if (!empty($optionsByRegexp)) {
            $client = new ScopingHttpClient($client, $optionsByRegexp);
        }

        return $client;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getHttpClientDefaultOptions()
    {
        return [];
    }
}
