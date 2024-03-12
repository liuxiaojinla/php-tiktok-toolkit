<?php

declare(strict_types=1);

namespace Xin\TiktokToolkit\Exceptions;

use Psr\Http\Message\ResponseInterface;

class HttpException extends Exception
{
    /**
     * @var ResponseInterface|null
     */
    public $response;

    /**
     * HttpException constructor.
     */
    public function __construct(string $message, ?ResponseInterface $response = null, int $code = 0)
    {
        parent::__construct($message, $code);

        $this->response = $response;

        if ($response) {
            $response->getBody()->rewind();
        }
    }
}
