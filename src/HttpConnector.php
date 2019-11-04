<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\ConnectionContext;
use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Connector\ConnectorOptions;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;

/**
 * Fetches data from an HTTP server via the PHP wrapper.
 *
 * Enhanced error reporting is achieved by ignoring HTTP error codes in the wrapper, instead throwing
 * HttpServerException which includes the body of the response in the error message.
 */
class HttpConnector implements Connector, ConnectorOptions
{
    private $options;

    public function __construct(HttpOptions $options = null)
    {
        $this->options = $options ?: new HttpOptions;
    }

    public function __clone()
    {
        $this->options = clone $this->options;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $source Source.
     *
     * @return HttpResponse Response.
     *
     * @throws \InvalidArgumentException Options is not an instance of HttpOptions.
     * @throws HttpConnectionException Failed to connect to source.
     * @throws HttpServerException Server sent an error code.
     */
    public function fetch(string $source): HttpResponse
    {
        $streamContext = stream_context_create([
            'http' =>
                // Instruct PHP to ignore HTTP error codes so Porter can handle them instead.
                ['ignore_errors' => true]
                + $this->options->extractHttpContextOptions()
            ,
            'ssl' => $this->options->getSslOptions()->extractSslContextOptions(),
        ]);

        if (false === $body = @file_get_contents($source, false, $streamContext)) {
            $error = error_get_last();
            throw new HttpConnectionException($error['message'], $error['type']);
        }

        $response = HttpResponse::fromPhpWrapper($http_response_header, $body);

        $code = $response->getStatusCode();
        if ($code < 200 || $code >= 400) {
            throw new HttpServerException(
                "HTTP server responded with error: $code \"{$response->getReasonPhrase()}\".\n\n$response",
                $response
            );
        }

        return $response;
    }

    /**
     * @return HttpOptions
     */
    public function getOptions(): EncapsulatedOptions
    {
        return $this->options;
    }
}
