<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\DataSource;
use ScriptFUSION\Type\StringType;

final class HttpDataSource implements DataSource
{
    private $url;

    private $method = 'GET';

    private $body = '';

    private $headers = [];

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function computeHash(): string
    {
        sort($this->headers);

        return md5(implode($this->headers) . "$this->method$this->url$this->body", true);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function addHeader(string $header): self
    {
        $this->headers[] = $header;

        return $this;
    }

    public function removeHeaders(string $name): self
    {
        foreach ($this->findHeaders($name) as $key => $_) {
            unset($this->headers[$key]);
        }

        return $this;
    }

    /**
     * Find the first header matching the specified name.
     *
     * @param string $name Header name.
     *
     * @return string|null Header if found, otherwise null.
     */
    public function findHeader(string $name): ?string
    {
        if ($headers = $this->findHeaders($name)) {
            return reset($headers);
        }

        return null;
    }

    /**
     * Find all headers matching the specified header name.
     *
     * @param string $name Header name.
     *
     * @return array Headers.
     */
    public function findHeaders(string $name): array
    {
        return array_filter($this->getHeaders(), static function ($header) use ($name): bool {
            return StringType::startsWith($header, "$name:");
        });
    }

    public function extractHttpContextOptions(): array
    {
        return [
            'method' => $this->method,
            'header' => $this->headers,
            'content' => $this->body,
        ];
    }
}
