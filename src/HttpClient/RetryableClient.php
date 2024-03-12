<?php

namespace Xin\TiktokToolkit\HttpClient;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\HttpClient\RetryableHttpClient;

trait RetryableClient
{
    /**
     * @param array<string, mixed> $config
     * @return $this
     */
    public function retry(array $config = [])
    {
        $config = RequestUtil::mergeDefaultRetryOptions($config);

        $strategy = new GenericRetryStrategy(
        // @phpstan-ignore-next-line
            (array)$config['status_codes'],
            // @phpstan-ignore-next-line
            (int)$config['delay'],
            // @phpstan-ignore-next-line
            (float)$config['multiplier'],
            // @phpstan-ignore-next-line
            (int)$config['max_delay'],
            // @phpstan-ignore-next-line
            (float)$config['jitter']
        );

        /** @phpstan-ignore-next-line */
        return $this->retryUsing($strategy, (int)$config['max_retries']);
    }

    /**
     * @param RetryStrategyInterface $strategy
     * @param int $maxRetries
     * @param LoggerInterface|null $logger
     * @return $this
     */
    public function retryUsing(
        RetryStrategyInterface $strategy,
        int                    $maxRetries = 3,
        ?LoggerInterface       $logger = null
    )
    {
        $this->client = new RetryableHttpClient($this->client, $strategy, $maxRetries, $logger);

        return $this;
    }
}
