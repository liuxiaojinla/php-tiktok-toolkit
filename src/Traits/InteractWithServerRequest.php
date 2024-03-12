<?php

namespace Xin\TiktokToolkit\Traits;

use EasyWeChat\Kernel\HttpClient\RequestUtil;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

trait InteractWithServerRequest
{
    /**
     * @var ServerRequestInterface|null
     */
    protected $request = null;

    public function getRequest(): ServerRequestInterface
    {
        if (!$this->request) {
            $this->request = RequestUtil::createDefaultServerRequest();
        }

        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param Request $symfonyRequest
     * @return $this
     */
    public function setRequestFromSymfonyRequest(Request $symfonyRequest)
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        $this->request = $psrHttpFactory->createRequest($symfonyRequest);

        return $this;
    }
}
