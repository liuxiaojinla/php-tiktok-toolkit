<?php

namespace Xin\TiktokToolkit\HttpClient;

use Xin\TiktokToolkit\Exceptions\InvalidArgumentException;
use Xin\TiktokToolkit\Exceptions\RuntimeException;
use Xin\TiktokToolkit\HttpClient\Form\File;
use Xin\TiktokToolkit\HttpClient\Form\Form;
use Xin\TiktokToolkit\Support\Str;

trait RequestWithPresets
{
    /**
     * @var array<string, string>
     */
    protected $prependHeaders = [];

    /**
     * @var array<string, mixed>
     */
    protected $prependParts = [];

    /**
     * @var array<string, mixed>
     */
    protected $presets = [];

    /**
     * @param array<string, mixed> $presets
     * @return $this
     */
    public function setPresets(array $presets)
    {
        $this->presets = $presets;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function withHeader(string $key, string $value)
    {
        $this->prependHeaders[$key] = $value;

        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->withHeader($key, $value);
        }

        return $this;
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @return $this
     * @throws InvalidArgumentException
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            // $client->with(['appid', 'mchid'])
            // $client->with(['appid' => 'wx1234567', 'mchid'])
            foreach ($key as $k => $v) {
                if (is_int($k) && is_string($v)) {
                    [$k, $v] = [$v, null];
                }

                $this->with($k, $v ?? $this->presets[$k] ?? null);
            }

            return $this;
        }

        $this->prependParts[$key] = $value ?? $this->presets[$key] ?? null;

        return $this;
    }

    /**
     * @param string $pathOrContents
     * @param string $formName
     * @param string|null $filename
     * @return $this
     * @throws RuntimeException
     */
    public function withFile(string $pathOrContents, string $formName = 'file', ?string $filename = null)
    {
        $file = is_file($pathOrContents) ? File::fromPath(
            $pathOrContents,
            $filename
        ) : File::withContents($pathOrContents, $filename);

        /**
         * @var array{headers<string, string>, body: string} $options
         */
        $options = Form::create([$formName => $file])->toOptions();

        $this->withHeaders($options['headers']);

        return $this->withOptions([
            'body' => $options['body'],
        ]);
    }

    /**
     * @param string $contents
     * @param string $formName
     * @param string|null $filename
     * @return $this
     * @throws RuntimeException
     */
    public function withFileContents(string $contents, string $formName = 'file', ?string $filename = null)
    {
        return $this->withFile($contents, $formName, $filename);
    }

    /**
     * @param array $files
     * @return $this
     * @throws RuntimeException
     */
    public function withFiles(array $files)
    {
        foreach ($files as $key => $value) {
            $this->withFile($value, $key);
        }

        return $this;
    }

    public function mergeThenResetPrepends(array $options, string $method = 'GET')
    {
        $name = in_array(strtoupper($method), ['GET', 'HEAD', 'DELETE']) ? 'query' : 'body';

        if (($options['headers']['Content-Type'] ?? $options['headers']['content-type'] ?? null) === 'application/json' || !empty($options['json'])) {
            $name = 'json';
        }

        if (($options['headers']['Content-Type'] ?? $options['headers']['content-type'] ?? null) === 'text/xml' || !empty($options['xml'])) {
            $name = 'xml';
        }

        if (!empty($this->prependParts)) {
            $options[$name] = array_merge($this->prependParts, $options[$name] ?? []);
        }

        if (!empty($this->prependHeaders)) {
            $options['headers'] = array_merge($this->prependHeaders, $options['headers'] ?? []);
        }

        $this->prependParts = [];
        $this->prependHeaders = [];

        return $options;
    }

    /**
     * @param string $method
     * @param mixed $value
     * @return RequestWithPresets
     * @throws InvalidArgumentException
     */
    public function handleMagicWithCall(string $method, $value = null)
    {
        // $client->withAppid();
        // $client->withAppid('wxf8b4f85f3a794e77');
        // $client->withAppidAs('sub_appid');
        if (!str_starts_with($method, 'with')) {
            throw new InvalidArgumentException(sprintf('The method "%s" is not supported.', $method));
        }

        $key = Str::snakeCase(substr($method, 4));

        // $client->withAppidAs('sub_appid');
        if (str_ends_with($key, '_as')) {
            $key = substr($key, 0, -3);

            [$key, $value] = [is_string($value) ? $value : $key, $this->presets[$key] ?? null];
        }

        return $this->with($key, $value);
    }
}
