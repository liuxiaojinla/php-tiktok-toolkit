<?php

namespace Xin\TiktokToolkit\Support;

use Xin\TiktokToolkit\Contracts\Aes;
use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;

class AesEcb implements Aes
{
    /**
     * @throws InvalidArgumentException
     */
    public static function encrypt(string $plaintext, string $key, ?string $iv = null): string
    {
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-ecb', $key, OPENSSL_RAW_DATA, (string) $iv);

        if ($ciphertext === false) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Encrypt AES ECB failed.');
        }

        return base64_encode($ciphertext);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function decrypt(string $ciphertext, string $key, ?string $iv = null): string
    {
        $plaintext = openssl_decrypt(
            base64_decode($ciphertext, true) ?: '',
            'aes-256-ecb',
            $key,
            OPENSSL_RAW_DATA,
            (string) $iv
        );

        if ($plaintext === false) {
            throw new InvalidArgumentException(openssl_error_string() ?: 'Decrypt AES ECB failed.');
        }

        return $plaintext;
    }
}
