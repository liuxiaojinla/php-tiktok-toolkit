<?php

namespace Xin\TiktokToolkit\Traits;

use Closure;
use JetBrains\PhpStorm\ArrayShape;
use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;
use Xin\TiktokToolkit\Server;

trait InteractWithHandlers
{
    /**
     * @var array<int, array{hash: string, handler: callable}>
     */
    protected $handlers = [];

    /**
     * @return array<int, array{hash: string, handler: callable}>
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @param callable $handler
     * @return Server
     * @throws InvalidArgumentException
     */
    public function with(callable $handler)
    {
        return $this->withHandler($handler);
    }

    /**
     * @param  $handler
     * @return InteractWithHandlers
     * @throws InvalidArgumentException
     */
    public function withHandler($handler)
    {
        $this->handlers[] = $this->createHandlerItem($handler);

        return $this;
    }

    /**
     * @param  $handler
     * @return array{hash: string, handler: callable}
     *
     * @throws InvalidArgumentException
     */
    #[ArrayShape(['hash' => 'string', 'handler' => 'callable'])]
    public function createHandlerItem($handler)
    {
        return [
            'hash' => $this->getHandlerHash($handler),
            'handler' => $this->makeClosure($handler),
        ];
    }

    /**
     * @param  $handler
     * @return string
     * @throws InvalidArgumentException
     */
    protected function getHandlerHash($handler): string
    {
        if (is_string($handler)) {
            return $handler;
        }

        if (is_array($handler)) {
            return is_string($handler[0]) ? $handler[0] . '::' . $handler[1] : get_class($handler[0]) . $handler[1];
        }

        if ($handler instanceof Closure || is_callable($handler)) {
            return spl_object_hash($handler);
        }

        throw new InvalidArgumentException('Invalid handler: ' . gettype($handler));
    }

    /**
     * @param  $handler
     * @return callable
     * @throws InvalidArgumentException
     */
    protected function makeClosure($handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (class_exists($handler) && method_exists($handler, '__invoke')) {
            /**
             * @psalm-suppress InvalidFunctionCall
             *
             * @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/5867
             */
            return function () use ($handler) {
                return (new $handler())(...func_get_args());
            };
        }

        throw new InvalidArgumentException(sprintf('Invalid handler: %s.', $handler));
    }

    /**
     * @param  $handler
     * @return InteractWithHandlers
     * @throws InvalidArgumentException
     */
    public function prepend($handler)
    {
        return $this->prependHandler($handler);
    }

    /**
     * @param  $handler
     * @return InteractWithHandlers
     * @throws InvalidArgumentException
     */
    public function prependHandler($handler)
    {
        array_unshift($this->handlers, $this->createHandlerItem($handler));

        return $this;
    }

    /**
     * @param  $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function without($handler)
    {
        return $this->withoutHandler($handler);
    }

    /**
     * @param  $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withoutHandler($handler)
    {
        $index = $this->indexOf($handler);

        if ($index > -1) {
            unset($this->handlers[$index]);
        }

        return $this;
    }

    /**
     * @param  $handler
     * @return int
     * @throws InvalidArgumentException
     */
    public function indexOf($handler)
    {
        foreach ($this->handlers as $index => $item) {
            if ($item['hash'] === $this->getHandlerHash($handler)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @param $value
     * @param  $handler
     * @return $this
     * @throws InvalidArgumentException
     */
    public function when($value, $handler)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $this);
        }

        if ($value) {
            return $this->withHandler($handler);
        }

        return $this;
    }

    /**
     * @param $result
     * @param mixed|null $payload
     * @return mixed
     */
    public function handle($result, $payload = null)
    {
        $next = $result = is_callable($result) ? $result : function ($p) use ($result) {
            return $result;
        };

        foreach (array_reverse($this->handlers) as $item) {
            $next = function ($p) use ($next, $result) {
                return $item['handler']($p, $next) ?? $result($p);
            };
        }

        return $next($payload);
    }

    /**
     * @param  $handler
     * @return bool
     * @throws InvalidArgumentException
     */
    public function has($handler)
    {
        return $this->indexOf($handler) > -1;
    }
}
