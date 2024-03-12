<?php

namespace Xin\TiktokToolkit\HttpClient;

use Closure;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Xin\TiktokToolkit\Contracts\AccessToken as AccessTokenInterface;
use Xin\TiktokToolkit\Contracts\RefreshableAccessToken as RefreshableAccessTokenInterface;

class AccessTokenExpiredRetryStrategy extends GenericRetryStrategy
{
    /**
     * @var AccessTokenInterface
     */
    protected $accessToken;

    /**
     * @var Closure|null
     */
    protected $decider = null;

    public function withAccessToken(AccessTokenInterface $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function decideUsing(Closure $decider): self
    {
        $this->decider = $decider;

        return $this;
    }

    public function shouldRetry(
        AsyncContext                 $context,
        ?string                      $responseContent,
        ?TransportExceptionInterface $exception
    ): ?bool
    {
        if ($responseContent && $this->decider && ($this->decider)($context, $responseContent, $exception)) {
            if ($this->accessToken instanceof RefreshableAccessTokenInterface) {
                return (bool)$this->accessToken->refresh();
            }

            return false;
        }

        return parent::shouldRetry($context, $responseContent, $exception);
    }
}
