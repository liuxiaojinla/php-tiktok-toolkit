<?php

namespace Xin\TiktokToolkit\Support;

class Str
{
    /**
     * From https://github.com/laravel/framework/blob/9.x/src/Illuminate/Support/Str.php#L632-L644
     */
    public static function random(int $length = 16): string
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            /** @phpstan-ignore-next-line */
            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    public static function snakeCase(string $string): string
    {
        return trim(strtolower((string)preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string)), '_');
    }
}
