<?php

namespace Xin\TiktokToolkit\Traits;

trait HasAttributes
{
    /**
     * @var array<int|string,mixed>
     */
    protected $attributes = [];

    /**
     * @param array<int|string,mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array<int|string,mixed>
     */
    public function toArray()
    {
        return $this->attributes;
    }

    public function toJson()
    {
        return json_encode($this->attributes);
    }

    public function has(string $key)
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * @param array<int|string,mixed> $attributes
     * @return $this
     */
    public function merge(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * @return array<int|string,mixed> $attributes
     */
    public function jsonSerialize()
    {
        return $this->attributes;
    }

    public function __set($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }

    public function __get(string $attribute)
    {
        return $this->attributes[$attribute] ?? null;
    }

    public function offsetExists($offset)
    {
        /** @phpstan-ignore-next-line */
        return array_key_exists($offset, $this->attributes);
    }

    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}
