<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Http\Client\RequestBody;
use ScriptFUSION\Porter\Connector\DataSource;

final class HttpDataSource implements DataSource
{
    private string $method = 'GET';
    private ?RequestBody $body = null;
    private array $headers = [];

    public function __construct(private readonly string $url)
    {
    }

    public function computeHash(): string
    {
        $body = $this->body?->createBodyStream()->read();

        return \md5("{$this->flattenHeaders()}$this->method$this->url$body", true);
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

    public function getBody(): ?RequestBody
    {
        return $this->body;
    }

    public function setBody(?RequestBody $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name][] = $value;

        return $this;
    }

    public function removeHeaders(string $name): self
    {
        unset($this->headers[$name]);

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
        if (isset($this->headers[$name])) {
            return reset($this->headers[$name]);
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
        return $this->headers[$name] ?? [];
    }

    /**
     * Flattens headers in a deterministic order using sorting.
     *
     * @return string Flattened headers.
     */
    private function flattenHeaders(): string
    {
        $flattened = '';

        ksort($this->headers);
        foreach ($this->headers as $name => $values) {
            sort($values);
            foreach ($values as $value) {
                $flattened .= $name . $value;
            }
        }

        return $flattened;
    }
}
