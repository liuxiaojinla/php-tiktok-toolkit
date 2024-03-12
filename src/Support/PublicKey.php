<?php

namespace Xin\TiktokToolkit\Support;

use Xin\TiktokToolkit\Exceptions\InvalidConfigException;
use function file_exists;
use function file_get_contents;
use function openssl_x509_parse;
use function str_starts_with;
use function strtoupper;

class PublicKey
{
    /**
     * @var string
     */
    public $certificate;

    /**
     * @param string $certificate
     */
    public function __construct(string $certificate)
    {
        if (file_exists($certificate)) {
            $this->certificate = "file://{$certificate}";
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public function getSerialNo()
    {
        $info = openssl_x509_parse($this->certificate);

        if ($info === false || !isset($info['serialNumberHex'])) {
            throw new InvalidConfigException('Read the $certificate failed, please check it whether or nor correct');
        }

        return strtoupper($info['serialNumberHex'] ?? '');
    }

    public function __toString()
    {
        if (str_starts_with($this->certificate, 'file://')) {
            return file_get_contents($this->certificate) ?: '';
        }

        return $this->certificate;
    }
}
