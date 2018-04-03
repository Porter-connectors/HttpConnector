<?php
namespace ScriptFUSION\Porter\Net\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use ScriptFUSION\Type\StringType;

/**
 * Represents an HTTP server response.
 */
final class HttpResponse implements ResponseInterface
{
    private $body;

    private $headers = [];

    private $version;

    private $statusCode;

    private $statusPhrase;

    private $previous;

    /**
     * @param array $headers
     * @param string $body
     */
    public function __construct(array $headers, $body = null)
    {
        $this->parseHeaders($headers);
        $this->body = "$body";
    }

    private function parseHeaders(array $headers)
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
                    $this->previous = new self(array_slice($headers, 0, key($headers)));
                }

                break;
            }

            $this->headers[$matches[1]][] = $matches[2];
        } while ($header = prev($headers));
    }

    private static function isProbablyVersionHeader($header)
    {
        return StringType::startsWith($header, 'HTTP/');
    }

    private function parseVersionHeader($header)
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

    public function withProtocolVersion($version)
    {
        throw new NotImplementedException;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
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

    public function getHeaderLine($name)
    {
        throw new NotImplementedException;
    }

    public function withHeader($name, $value)
    {
        throw new NotImplementedException;
    }

    public function withAddedHeader($name, $value)
    {
        throw new NotImplementedException;
    }

    public function withoutHeader($name)
    {
        throw new NotImplementedException;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        throw new NotImplementedException;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        throw new NotImplementedException;
    }

    public function getReasonPhrase()
    {
        return $this->statusPhrase;
    }

    /**
     * Gets the previous response.
     *
     * @return HttpResponse|null
     */
    public function getPrevious()
    {
        return $this->previous;
    }
}
