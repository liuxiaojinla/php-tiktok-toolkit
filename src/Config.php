<?php

namespace Xin\TiktokToolkit;

use ArrayAccess;
use Xin\TiktokToolkit\Contracts\Config as ConfigInterface;
use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;
use Xin\TiktokToolkit\Support\Arr;

/**
 * @implements ArrayAccess<mixed, mixed>
 */
class Config implements ArrayAccess, ConfigInterface
{
    /**
     * @var array<string>
     */
    protected $requiredKeys = [
        'client_key',
        'client_secret',
//        'aes_key',
    ];

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @param array<string, mixed> $items
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        array $items = []
    )
    {
        $this->items = $items;
        $this->checkMissingKeys();
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return Arr::has($this->items, $key);
    }

    /**
     * @param array<string,mixed>|ConfigInterface $config
     * @return ConfigInterface|array[]|Config
     * @throws InvalidArgumentException
     */
    public static function form($config)
    {
        if ($config instanceof ConfigInterface) {
            return $config;
        }

        return new Config(is_array($config) ? $config : (array)$config);
    }

    /**
     * @param array<string>|string $key
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    /**
     * @param array<string> $keys
     * @return array<string, mixed>
     */
    public function getMany(array $keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    public function set(string $key, $value = null)
    {
        Arr::set($this->items, $key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return $this->has(strval($offset));
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->get(strval($offset));
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->set(strval($offset), $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        $this->set(strval($offset), null);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function checkMissingKeys()
    {
        if (empty($this->requiredKeys)) {
            return true;
        }

        $missingKeys = [];

        foreach ($this->requiredKeys as $key) {
            if (!$this->has($key)) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new InvalidArgumentException(sprintf("\"%s\" cannot be empty.\r\n", implode(',', $missingKeys)));
        }

        return true;
    }
}
