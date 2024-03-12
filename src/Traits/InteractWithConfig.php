<?php

declare(strict_types=1);

namespace Xin\TiktokToolkit\Traits;

use Xin\TiktokToolkit\Config;
use Xin\TiktokToolkit\Contracts\Config as ConfigInterface;
use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;

trait InteractWithConfig
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param array<string,mixed>|ConfigInterface $config
     *
     * @throws InvalidArgumentException
     */
    public function __construct($config)
    {
        $this->config = Config::form($config);
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;

        return $this;
    }
}
