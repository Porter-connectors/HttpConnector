<?php
namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\ConnectionContext;
use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Connector\ConnectorOptions;
use ScriptFUSION\Porter\Net\UrlBuilder;

/**
 * Fetches data from an HTTP server via the PHP wrapper.
 *
 * Enhanced error reporting is achieved by ignoring HTTP error codes in the wrapper, instead throwing
 * HttpServerException which includes the body of the response in the error message.
 */
class HttpConnector implements Connector, ConnectorOptions
{
    /** @var HttpOptions */
    private $options;

    /** @var UrlBuilder */
    private $urlBuilder;

    /** @var string */
    private $baseUrl;

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
     * @param ConnectionContext $context Runtime connection settings and methods.
     * @param string $source Source.
     *
     * @return HttpResponse Response.
     *
     * @throws \InvalidArgumentException Options is not an instance of HttpOptions.
     * @throws HttpConnectionException Failed to connect to source.
     * @throws HttpServerException Server sent an error code.
     */
    public function fetch(ConnectionContext $context, $source)
    {
        $url = $this->getOrCreateUrlBuilder()->buildUrl(
            $source,
            $this->options->getQueryParameters(),
            $this->getBaseUrl()
        );

        $streamContext = stream_context_create([
            'http' =>
                // Instruct PHP to ignore HTTP error codes so Porter can handle them instead.
                ['ignore_errors' => true]
                + $this->options->extractHttpContextOptions()
            ,
            'ssl' => $this->options->getSslOptions()->extractSslContextOptions(),
        ]);

        return $context->retry(function () use ($url, $streamContext) {
            if (false === $body = @file_get_contents($url, false, $streamContext)) {
                $error = error_get_last();
                throw new HttpConnectionException($error['message'], $error['type']);
            }

            $response = new HttpResponse($http_response_header, $body);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
                throw new HttpServerException(
                    "HTTP server responded with error: \"{$response->getReasonPhrase()}\".\n\n$response",
                    $response
                );
            }

            return $response;
        });
    }

    /**
     * @return HttpOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    private function getOrCreateUrlBuilder()
    {
        return $this->urlBuilder ?: $this->urlBuilder = new UrlBuilder($this->options);
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param $baseUrl
     *
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = "$baseUrl";

        return $this;
    }
}
