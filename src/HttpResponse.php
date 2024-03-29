<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Http\Client\Response;

/**
 * Represents an HTTP server response.
 */
final class HttpResponse
{
    private array $headers;
    private string $version;
    private int $statusCode;
    private string $statusPhrase;
    private ?self $previous;
    private string $bufferedBody;

    public function __construct(Response $ampResponse)
    {
        $this->headers = $ampResponse->getHeaders();
        $this->version = $ampResponse->getProtocolVersion();
        $this->statusCode = $ampResponse->getStatus();
        $this->statusPhrase = $ampResponse->getReason();
        $this->previous = $ampResponse->getPreviousResponse() ? new self($ampResponse->getPreviousResponse()) : null;
        // We must buffer body immediately, otherwise we get memory leaks.
        $this->bufferedBody = $ampResponse->getBody()->isReadable() ? $ampResponse->getBody()->buffer() : '';
    }

    public function __toString(): string
    {
        return $this->getBody();
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists(self::normalizeHeaderName($name), $this->headers);
    }

    public function getHeader(string $name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        return $this->headers[self::normalizeHeaderName($name)];
    }

    public function getBody(): string
    {
        return $this->bufferedBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->statusPhrase;
    }

    /**
     * Gets the previous response.
     */
    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    private static function normalizeHeaderName(string $headerName): string
    {
        return strtolower($headerName);
    }
}
