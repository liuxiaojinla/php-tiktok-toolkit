<?php

declare(strict_types=1);

namespace Xin\TiktokToolkit\Contracts;

interface Arrayable
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray();
}
