<?php

namespace Xin\TiktokToolkit\Traits;

use Xin\TiktokToolkit\HttpClient\AccessTokenAwareClient;

trait InteractWithClient
{
    /**
     * @var AccessTokenAwareClient|null
     */
    protected $client = null;

    /**
     * @return AccessTokenAwareClient|null
     */
    public function getClient(): AccessTokenAwareClient
    {
        if (!$this->client) {
            $this->client = $this->createClient();
        }

        return $this->client;
    }

    /**
     * @param AccessTokenAwareClient $client
     * @return $this
     */
    public function setClient(AccessTokenAwareClient $client)
    {
        $this->client = $client;

        return $this;
    }

    abstract public function createClient();
}
