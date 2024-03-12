<?php

declare(strict_types=1);

namespace Xin\TiktokToolkit;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Xin\TiktokToolkit\Contracts\VerifyTicket as VerifyTicketInterface;
use Xin\TiktokToolkit\Exceptions\RuntimeException;

class VerifyTicket implements VerifyTicketInterface
{
    /**
     * @var CacheInterface|Psr16Cache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $clientKey;

    /**
     * @var string
     */
    protected $key;

    public function __construct(
        string          $clientKey,
        ?string         $key = null,
        ?CacheInterface $cache = null
    ) {
        $this->clientKey = $clientKey;
        $this->key = $key;

        $this->cache = $cache ?? new Psr16Cache(new FilesystemAdapter(
            'easywechat',
            1500
        ));
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key ?? $this->key = sprintf('open_platform.verify_ticket.%s', $this->clientKey);
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey(string $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param string $ticket
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setTicket(string $ticket)
    {
        $this->cache->set($this->getKey(), $ticket, 6000);

        return $this;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function getTicket(): string
    {
        $ticket = $this->cache->get($this->getKey());

        if (!$ticket || !is_string($ticket)) {
            throw new RuntimeException('No component_verify_ticket found.');
        }

        return $ticket;
    }
}
