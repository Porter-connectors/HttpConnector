<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\ByteStream\Payload;
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
    private Payload $body;
    private string $bodyBuffer;

    public function __construct(Response $ampResponse)
    {
        $this->headers = $ampResponse->getHeaders();
        $this->version = $ampResponse->getProtocolVersion();
        $this->statusCode = $ampResponse->getStatus();
        $this->statusPhrase = $ampResponse->getReason();
        $this->previous = $ampResponse->getPreviousResponse() ? new self($ampResponse->getPreviousResponse()) : null;
        $this->body = $ampResponse->getBody();
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
        return $this->bodyBuffer ??= $this->body->buffer();
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
