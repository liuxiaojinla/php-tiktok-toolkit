<?php

namespace Xin\TiktokToolkit\HttpClient;

use ArrayAccess;
use Closure;
use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Component\HttpClient\Response\StreamWrapper;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;
use Xin\TiktokToolkit\Contracts\Arrayable;
use Xin\TiktokToolkit\Contracts\Jsonable;
use Xin\TiktokToolkit\Exceptions\BadMethodCallException;
use Xin\TiktokToolkit\Exceptions\BadResponseException;

/**
 * @implements ArrayAccess<array-key, mixed>
 *
 * @see ResponseInterface
 */
class Response implements Arrayable, ArrayAccess, Jsonable, ResponseInterface, StreamableInterface
{
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var Closure|null
     */
    protected $failureJudge = null;
    /**
     * @var bool
     */
    protected $throw = true;

    /**
     * @param ResponseInterface $response
     * @param Closure|null $failureJudge
     * @param bool $throw
     */
    public function __construct(
        ResponseInterface $response,
        ?Closure          $failureJudge = null,
        bool              $throw = true
    )
    {
        $this->response = $response;
        $this->failureJudge = $failureJudge;
        $this->throw = $throw;
    }

    public function throw(bool $throw = true)
    {
        $this->throw = $throw;

        return $this;
    }

    public function throwOnFailure()
    {
        return $this->throw(true);
    }

    public function quietly()
    {
        return $this->throw(false);
    }

    public function judgeFailureUsing(callable $callback)
    {
        $this->failureJudge = $callback instanceof Closure ? $callback : function (Response $response) use ($callback) {
            $callback($response);
        };

        return $this;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function isSuccessful()
    {
        return !$this->isFailed();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function isFailed()
    {
        if ($this->is('text') && $this->failureJudge) {
            return (bool)($this->failureJudge)($this);
        }

        try {
            return $this->getStatusCode() >= 400;
        } catch (Throwable $e) {
            return true;
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function toArray(?bool $throw = null): array
    {
        if ($throw === null) {
            $throw = $this->throw;
        }

        if ('' === $content = $this->response->getContent($throw)) {
            throw new BadResponseException('Response body is empty.');
        }

        $contentType = $this->getHeaderLine('content-type', $throw);

        return $this->response->toArray($throw);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function toJson(?bool $throw = null)
    {
        return json_encode($this->toArray($throw), JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     * @throws BadMethodCallException
     */
    public function toStream(?bool $throw = null)
    {
        if ($this->response instanceof StreamableInterface) {
            return $this->response->toStream($throw ?? $this->throw);
        }

        if ($throw) {
            throw new BadMethodCallException(sprintf('%s does\'t implements %s', get_class($this->response), StreamableInterface::class));
        }

        return StreamWrapper::createResource(new MockResponse());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function toDataUrl(): string
    {
        return 'data:' . $this->getHeaderLine('content-type') . ';base64,' . base64_encode($this->getContent());
    }

    public function toPsrResponse(?ResponseFactoryInterface $responseFactory = null, ?StreamFactoryInterface $streamFactory = null): \Psr\Http\Message\ResponseInterface
    {
        if ($streamFactory === null) {
            $streamFactory = $responseFactory instanceof StreamFactoryInterface ? $responseFactory : null;
        }

        if ($responseFactory === null || $streamFactory === null) {
            if (!class_exists(Psr17Factory::class) && !class_exists(Psr17FactoryDiscovery::class)) {
                throw new LogicException('You cannot use the "Symfony\Component\HttpClient\Psr18Client" as no PSR-17 factories have been provided. Try running "composer require nyholm/psr7".');
            }

            try {
                $psr17Factory = class_exists(Psr17Factory::class, false) ? new Psr17Factory() : null;
                if ($responseFactory === null) {
                    $responseFactory = $psr17Factory ?? Psr17FactoryDiscovery::findResponseFactory();
                }

                if ($streamFactory === null) {
                    $streamFactory = $psr17Factory ?? Psr17FactoryDiscovery::findStreamFactory();
                }
            } catch (NotFoundException $e) {
                throw new LogicException('You cannot use the "Symfony\Component\HttpClient\HttplugClient" as no PSR-17 factories have been found. Try running "composer require nyholm/psr7".', 0, $e);
            }
        }

        $psrResponse = $responseFactory->createResponse($this->getStatusCode());

        foreach ($this->getHeaders(false) as $name => $values) {
            foreach ($values as $value) {
                $psrResponse = $psrResponse->withAddedHeader($name, $value);
            }
        }

        $body = $this->response instanceof StreamableInterface ? $this->toStream(false) : StreamWrapper::createResource($this->response);
        $body = $streamFactory->createStreamFromResource($body);

        if ($body->isSeekable()) {
            $body->seek(0);
        }

        return $psrResponse->withBody($body);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws BadResponseException
     */
    public function saveAs(string $filename): string
    {
        try {
            file_put_contents($filename, $this->response->getContent(true));
        } catch (Throwable $e) {
            throw new BadResponseException(sprintf(
                'Cannot save response to %s: %s',
                $filename,
                $this->response->getContent(false)
            ), $e->getCode(), $e);
        }

        return '';
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->toArray());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function offsetGet($offset)
    {
        return $this->toArray()[$offset] ?? null;
    }

    /**
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Response is immutable.');
    }

    /**
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Response is immutable.');
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __call(string $name, array $arguments)
    {
        return $this->response->{$name}(...$arguments);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getHeaders(?bool $throw = null): array
    {
        return $this->response->getHeaders($throw ?? $this->throw);
    }

    public function getContent(?bool $throw = null): string
    {
        return $this->response->getContent($throw ?? $this->throw);
    }

    public function cancel(): void
    {
        $this->response->cancel();
    }

    public function getInfo(?string $type = null)
    {
        return $this->response->getInfo($type);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws BadResponseException
     */
    public function __toString(): string
    {
        return $this->toJson() ?: '';
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function hasHeader(string $name, ?bool $throw = null)
    {
        return isset($this->getHeaders($throw)[$name]);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getHeader(string $name, ?bool $throw = null)
    {
        $name = strtolower($name);
        $throw = $throw === null ? $this->throw : $throw;

        return $this->hasHeader($name, $throw) ? $this->getHeaders($throw)[$name] : [];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getHeaderLine(string $name, ?bool $throw = null): string
    {
        $name = strtolower($name);
        if ($throw === null) {
            $throw = $this->throw;
        }

        return $this->hasHeader($name, $throw) ? implode(',', $this->getHeader($name, $throw)) : '';
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function is(string $type)
    {
        $contentType = $this->getHeaderLine('content-type');

        switch (strtolower($type)) {
            case  'json' :
                return str_contains($contentType, '/json');
            case'xml' :
                return str_contains($contentType, '/xml');
            case 'html' :
                return str_contains($contentType, '/html');
            case  'image' :
                return str_contains($contentType, 'image/');
            case  'audio' :
                return str_contains($contentType, 'audio/');
            case  'video':
                return str_contains($contentType, 'video/');
            case 'text':
                return str_contains($contentType, 'text/')
                    || str_contains($contentType, '/json')
                    || str_contains($contentType, '/xml');
            default :
                return false;
        }
    }
}
