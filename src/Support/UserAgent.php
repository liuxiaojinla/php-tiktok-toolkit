<?php

declare(strict_types=1);

namespace Xin\TiktokToolkit\Support;

use Composer\InstalledVersions;

class UserAgent
{
    /**
     * @param  array<string>  $appends
     */
    public static function create(array $appends = [])
    {
        $value = array_map('strval', $appends);

        if (defined('HHVM_VERSION')) {
            array_unshift($value, 'HHVM/'.HHVM_VERSION);
        }

        $disabledFunctions = explode(',', ini_get('disable_functions') ?: '');

        if (extension_loaded('curl') && function_exists('curl_version')) {
            array_unshift($value, 'curl/'.(curl_version() ?: ['version' => 'unknown'])['version']);
        }

        if (! ini_get('safe_mode')
            && function_exists('php_uname')
            && ! in_array('php_uname', $disabledFunctions, true)
        ) {
            $osName = 'OS/'.php_uname('s').'/'.php_uname('r');
            array_unshift($value, $osName);
        }

        if (class_exists(InstalledVersions::class)) {
            array_unshift($value, 'tiktok-toolkit/'.((string) InstalledVersions::getVersion('xin/php-tiktok-toolkit')));
        }

        return trim(implode(' ', $value));
    }
}
