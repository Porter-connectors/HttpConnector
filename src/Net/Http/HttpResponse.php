<?php
namespace ScriptFUSION\Porter\Net\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Represents an HTTP server response.
 */
final class HttpResponse implements ResponseInterface
{
    private $body;

    private $headers;

    private $version;

    private $statusCode;

    private $statusPhrase;

    /**
     * @param string $body
     * @param array $headers
     * @param string $version
     * @param int $statusCode
     * @param string $statusPhrase
     */
    public function __construct($body, array $headers, $version, $statusCode, $statusPhrase)
    {
        $this->body = "$body";
        $this->headers = $this->parseHeaders($headers);
        $this->version = "$version";
        $this->statusCode = (int)$statusCode;
        $this->statusPhrase = "$statusPhrase";
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

    private function parseHeaders(array $headers)
    {
        $parsedHeaders = [];

        foreach ($headers as $header) {
            if (!preg_match('[^([^:]+):\h*(.*)$]', $header, $matches)) {
                throw new \InvalidArgumentException("Invalid header: \"$header\".");
            }

            $parsedHeaders[$matches[1]][] = $matches[2];
        }

        return $parsedHeaders;
    }
}
