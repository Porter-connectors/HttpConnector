<?php
namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\ConnectionContext;
use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Net\UrlBuilder;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;

/**
 * Fetches data from an HTTP server via the PHP wrapper.
 *
 * Enhanced error reporting is achieved by ignoring HTTP error codes in the wrapper, instead throwing
 * HttpServerException which includes the body of the response in the error message.
 */
class HttpConnector implements Connector
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

    /**
     * {@inheritdoc}
     *
     * @param ConnectionContext $context Runtime connection settings and methods.
     * @param string $source Source.
     * @param EncapsulatedOptions|null $options Optional. Options.
     *
     * @return string Response.
     *
     * @throws \InvalidArgumentException Options is not an instance of HttpOptions.
     * @throws HttpConnectionException Failed to connect to source.
     * @throws HttpServerException Server sent an error code.
     */
    public function fetch(ConnectionContext $context, $source, EncapsulatedOptions $options = null)
    {
        if ($options && !$options instanceof HttpOptions) {
            throw new \InvalidArgumentException('Options must be an instance of HttpOptions.');
        }

        $url = $this->getOrCreateUrlBuilder()->buildUrl(
            $source,
            $options ? $options->getQueryParameters() : [],
            $this->getBaseUrl()
        );

        $streamContext = stream_context_create([
            'http' =>
                // Instruct PHP to ignore HTTP error codes so Porter can handle them instead.
                ['ignore_errors' => true]
                + ($options ? $options->extractHttpContextOptions() : [])
                + $this->options->extractHttpContextOptions()
            ,
            'ssl' =>
                ($options ? $options->getSslOptions()->extractSslContextOptions() : [])
                + $this->options->getSslOptions()->extractSslContextOptions()
            ,
        ]);

        return $context->retry(function () use ($url, $streamContext) {
            if (false === $response = @file_get_contents($url, false, $streamContext)) {
                $error = error_get_last();
                throw new HttpConnectionException($error['message'], $error['type']);
            }

            $code = explode(' ', $http_response_header[0], 3)[1];
            if ($code < 200 || $code >= 400) {
                throw new HttpServerException(
                    "HTTP server responded with error: \"$http_response_header[0]\".\n\n$response",
                    $code,
                    $response
                );
            }

            return $response;
        });
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
