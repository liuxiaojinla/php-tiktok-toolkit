<?php

namespace Xin\TiktokToolkit\HttpClient\Form;

use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\Part\DataPart;
use Xin\TiktokToolkit\Exceptions\RuntimeException;

class File extends DataPart
{
    /**
     * @throws RuntimeException
     */
    public static function from(
        string $pathOrContents,
        ?string $filename = null,
        ?string $contentType = null,
        ?string $encoding = null
    ): DataPart {
        if (file_exists($pathOrContents)) {
            return static::fromPath($pathOrContents, $filename, $contentType);
        }

        return static::fromContents($pathOrContents, $filename, $contentType, $encoding);
    }

    /**
     * @throws RuntimeException
     */
    public static function fromContents(
        string $contents,
        ?string $filename = null,
        ?string $contentType = null,
        ?string $encoding = null
    ): DataPart {
        if ($contentType === null) {
            $mimeTypes = new MimeTypes();

            if ($filename) {
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $contentType = $mimeTypes->getMimeTypes($ext)[0] ?? 'application/octet-stream';
            } else {
                $tmp = tempnam(sys_get_temp_dir(), 'tiktok');
                if (! $tmp) {
                    throw new RuntimeException('Failed to create temporary file.');
                }

                file_put_contents($tmp, $contents);
                $contentType = $mimeTypes->guessMimeType($tmp) ?? 'application/octet-stream';
                $filename = md5($contents).'.'.($mimeTypes->getExtensions($contentType)[0] ?? null);
            }
        }

        return new self($contents, $filename, $contentType, $encoding);
    }

    /**
     * @throws RuntimeException
     *
     * @deprecated since EasyWeChat 7.0, use fromContents() instead
     */
    public static function withContents(
        string $contents,
        ?string $filename = null,
        ?string $contentType = null,
        ?string $encoding = null
    ): DataPart {
        return self::fromContents(...func_get_args());
    }
}
