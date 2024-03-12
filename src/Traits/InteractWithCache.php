<?php

namespace Xin\TiktokToolkit\Traits;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

trait InteractWithCache
{

    /**
     * @var CacheInterface
     */
    protected $cache = null;

    /**
     * @var int
     */
    protected $cacheLifetime = 1500;

    /**
     * @var string
     */
    protected $cacheNamespace = 'tiktok';

    /**
     * @return int
     */
    public function getCacheLifetime()
    {
        return $this->cacheLifetime;
    }

    /**
     * @param int $cacheLifetime
     * @return void
     */
    public function setCacheLifetime(int $cacheLifetime)
    {
        $this->cacheLifetime = $cacheLifetime;
    }

    /**
     * @return string
     */
    public function getCacheNamespace()
    {
        return $this->cacheNamespace;
    }

    /**
     * @param string $cacheNamespace
     * @return void
     */
    public function setCacheNamespace(string $cacheNamespace)
    {
        $this->cacheNamespace = $cacheNamespace;
    }

    /**
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return CacheInterface|Psr16Cache
     */
    public function getCache(): CacheInterface
    {
        if (!$this->cache) {
            $this->cache = new Psr16Cache(new FilesystemAdapter($this->cacheNamespace, $this->cacheLifetime));
        }

        return $this->cache;
    }
}
