<?php

namespace Xin\TiktokToolkit;

use Closure;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xin\TiktokToolkit\Contracts\Server as ServerInterface;
use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;
use Xin\TiktokToolkit\HttpClient\RequestUtil;
use Xin\TiktokToolkit\Traits\InteractWithHandlers;

class Server implements ServerInterface
{
    use InteractWithHandlers;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var Closure|null
     */
    protected $defaultVerifyTicketHandler = null;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @param Encryptor $encryptor
     * @param ServerRequestInterface|null $request
     */
    public function __construct(
        Encryptor               $encryptor,
        ?ServerRequestInterface $request = null
    )
    {
        $this->encryptor = $encryptor;
        $this->request = $request ?? RequestUtil::createDefaultServerRequest();
    }

    /**
     * @return ResponseInterface
     * @throws InvalidArgumentException
     */
    public function serve(): ResponseInterface
    {
        if ($str = $this->request->getQueryParams()['echostr'] ?? '') {
            return new Response(200, [], $str);
        }

        $message = json_decode($this->request->getBody(), true);
        $this->prepend($this->decryptRequestMessage());
        $response = $this->handle(new Response(200, [], 'success'), $message);

        return ServerResponse::make($response);
    }

    /**
     * @param callable $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function handleAuthorized(callable $handler)
    {
        $this->with(function ($message, Closure $next) use ($handler) {
            return $message['event'] === 'authorized' ? $handler($message, $next) : $next($message);
        });

        return $this;
    }

    /**
     * @param callable $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function handleUnauthorized(callable $handler)
    {
        return $this->handleEvent($handler, 'authorization.removed');
    }

    /**
     * @param callable $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function handleAuthorizeUpdated(callable $handler)
    {
        return $this->handleEvent($handler, 'authorization.removed');
    }

    /**
     * @param callable $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function handleVideoUploadFailed(callable $handler)
    {
        return $this->handleEvent($handler, 'video.upload.failed');
    }

    /**
     * @param callable $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function handleVideoUploadCompleted(callable $handler)
    {
        return $this->handleEvent($handler, 'video.publish.completed');
    }
    /**
     * @param callable $handler
     * @param string $event
     * @return $this
     * @throws InvalidArgumentException
     */
    public function handleEvent(callable $handler, string $event)
    {
        $this->with(function ($message, Closure $next) use ($handler, $event) {
            return $message['event'] === $event ? $handler($message, $next) : $next($message);
        });

        return $this;
    }


    /**
     * @param callable $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withDefaultVerifyTicketHandler(callable $handler)
    {
        $this->defaultVerifyTicketHandler = function () use ($handler) {
            return $handler(...func_get_args());
        };
        $this->handleVerifyTicketRefreshed($this->defaultVerifyTicketHandler);

        return $this;
    }

    /**
     * @param callable $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function handleVerifyTicketRefreshed(callable $handler)
    {
        if ($this->defaultVerifyTicketHandler) {
            $this->withoutHandler($this->defaultVerifyTicketHandler);
        }

        $this->with(function ($message, Closure $next) use ($handler) {
            return $message['event'] === 'component_verify_ticket' ? $handler($message, $next) : $next($message);
        });

        return $this;
    }

    /**
     * @return Closure
     */
    protected function decryptRequestMessage(): Closure
    {
        $query = $this->request->getQueryParams();

        return function ($message, Closure $next) use ($query) {
            return $next($message);
        };
    }
}
