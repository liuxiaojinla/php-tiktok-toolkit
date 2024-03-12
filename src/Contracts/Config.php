<?php

declare(strict_types=1);

namespace Xin\TiktokToolkit\Contracts;

use ArrayAccess;

/**
 * @extends ArrayAccess<string, mixed>
 */
interface Config extends ArrayAccess
{
    /**
     * @return array<string,mixed>
     */
    public function all();

    public function has(string $key);

    public function set(string $key, $value = null);

    /**
     * @param array<string>|string $key
     */
    public function get($key, $default = null);
}
