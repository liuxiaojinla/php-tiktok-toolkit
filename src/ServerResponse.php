<?php

namespace Xin\TiktokToolkit;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ServerResponse implements ResponseInterface
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        $this->response->getBody()->rewind();
    }

    #[Pure]
    public static function make(ResponseInterface $response): ServerResponse
    {
        if ($response instanceof ServerResponse) {
            return $response;
        }

        return new self($response);
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version): MessageInterface
    {
        return $this->response->withProtocolVersion($version);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name): array
    {
        return $this->response->getHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value): MessageInterface
    {
        return $this->response->withHeader($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value): MessageInterface
    {
        return $this->response->withAddedHeader($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name): MessageInterface
    {
        return $this->response->withoutHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->response->withBody($body);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $this->response->withStatus($code, $reasonPhrase);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * @link https://github.com/symfony/http-foundation/blob/6.1/Response.php
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function sendHeaders()
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        foreach ($this->getHeaders() as $name => $values) {
            $replace = strcasecmp($name, 'Content-Type') === 0;

            foreach ($values as $value) {
                header($name . ': ' . $value, $replace, $this->getStatusCode());
            }
        }

        header(
            sprintf(
                'HTTP/%s %s %s',
                $this->getProtocolVersion(),
                $this->getStatusCode(),
                $this->getReasonPhrase()
            ),
            true,
            $this->getStatusCode()
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function sendContent()
    {
        echo $this->getBody();

        return $this;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @link https://github.com/symfony/http-foundation/blob/6.1/Response.php
     *
     * @final
     * @param int $targetLevel
     * @param bool $flush
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush)
    {
        $status = ob_get_status(true);
        $level = count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $headers = $this->getHeaders();
        $headersString = '';

        if (!empty($headers)) {
            ksort($headers);

            $max = max(array_map('strlen', array_keys($headers))) + 1;

            foreach ($headers as $name => $values) {
                $name = ucwords($name, '-');
                foreach ($values as $value) {
                    $headersString .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
                }
            }
        }

        return sprintf(
                'HTTP/%s %s %s',
                $this->getProtocolVersion(),
                $this->getStatusCode(),
                $this->getReasonPhrase()
            ) . "\r\n" .
            $headersString . "\r\n" .
            $this->getBody();
    }
}
