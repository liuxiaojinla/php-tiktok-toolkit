<?php

namespace Xin\TiktokToolkit\Support;

class PrivateKey
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string|null
     */
    protected $passphrase;

    /**
     * @param string $key
     * @param string|null $passphrase
     */
    public function __construct(string $key, string $passphrase = null)
    {
        $this->key = $key;
        $this->passphrase = $passphrase;

        if (file_exists($key)) {
            $this->key = "file://{$key}";
        }
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        if (str_starts_with($this->key, 'file://')) {
            return file_get_contents($this->key) ?: '';
        }

        return $this->key;
    }

    /**
     * @return string|null
     */
    public function getPassphrase()
    {
        return $this->passphrase;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getKey();
    }
}
