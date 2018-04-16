<?php
namespace ScriptFUSION\Porter\Net\Http;

use Amp\Artax\Response;
use ScriptFUSION\Type\StringType;

/**
 * Represents an HTTP server response.
 */
final class HttpResponse
{
    private $body;

    private $headers = [];

    private $version;

    private $statusCode;

    private $statusPhrase;

    private $previous;

    private function __construct()
    {
        // Intentionally empty. Use factory methods.
    }

    public static function fromPhpWrapper(array $headers, string $body = null): self
    {
        $response = new self;

        $response->parseHeaders($headers);
        $response->body = "$body";

        return $response;
    }

    public static function fromArtaxResponse(Response $artax, string $body): self
    {
        $response = new self;

        $response->headers = $artax->getHeaders();
        $response->version = $artax->getProtocolVersion();
        $response->statusCode = $artax->getStatus();
        $response->statusPhrase = $artax->getReason();
        $response->body = $body;

        return $response;
    }

    private function parseHeaders(array $headers): void
    {
        $header = end($headers);

        // Iterate headers in reverse because they may represent multiple responses.
        do {
            if (!preg_match('[^([^:\h]+):\h*(.*)$]', $header, $matches)) {
                if (!self::isProbablyVersionHeader($header)) {
                    throw new \InvalidArgumentException("Invalid header: \"$header\".");
                }

                $this->parseVersionHeader($header);

                // If there are further headers, recursively delegate to parent responses.
                if (key($headers) > 0) {
                    $this->previous = self::fromPhpWrapper(\array_slice($headers, 0, key($headers)));
                }

                break;
            }

            $this->headers[$matches[1]][] = $matches[2];
        } while ($header = prev($headers));
    }

    private static function isProbablyVersionHeader($header): bool
    {
        return StringType::startsWith($header, 'HTTP/');
    }

    private function parseVersionHeader($header): void
    {
        @list($version, $statusCode, $this->statusPhrase) = explode(' ', $header, 3);
        $this->statusCode = (int)$statusCode;
        $this->version = explode('/', $version, 2)[1];
    }

    public function __toString()
    {
        return $this->body;
    }

    public function getProtocolVersion()
    {
        return $this->version;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return array_key_exists($name, $this->headers);
    }

    public function getHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        return $this->headers[$name];
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getReasonPhrase()
    {
        return $this->statusPhrase;
    }

    /**
     * Gets the previous response.
     */
    public function getPrevious(): ?HttpResponse
    {
        return $this->previous;
    }
}
