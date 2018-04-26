<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Artax\DefaultClient;
use Amp\Artax\DnsException;
use Amp\Artax\Request;
use Amp\Artax\Response;
use Amp\Artax\SocketException;
use Amp\Artax\TimeoutException;
use Amp\Socket\CryptoException;
use ScriptFUSION\Porter\Connector\AsyncConnector;
use ScriptFUSION\Porter\Connector\ConnectionContext;
use ScriptFUSION\Porter\Connector\ConnectorOptions;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;

class AsyncHttpConnector implements AsyncConnector, ConnectorOptions
{
    private $options;

    public function __construct(AsyncHttpOptions $options = null)
    {
        $this->options = $options ?: new AsyncHttpOptions;
    }

    public function __clone()
    {
        $this->options = clone $this->options;
    }

    public function fetchAsync(string $source, ConnectionContext $context)
    {
        $client = new DefaultClient($this->options->getCookieJar());
        $client->setOptions($this->options->extractArtaxOptions());

        try {
            /** @var Response $response */
            $response = yield $client->request($this->createRequest($source));
            $body = yield $response->getBody();
            // Retry HTTP timeouts, socket timeouts, DNS resolution and crypto negotiation errors.
        } catch (TimeoutException | SocketException | DnsException | CryptoException $exception) {
            // Convert exception to recoverable exception.
            throw new HttpConnectionException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $response = HttpResponse::fromArtaxResponse($response, $body);

        $code = $response->getStatusCode();
        if ($code < 200 || $code >= 400) {
            throw new HttpServerException(
                "HTTP server responded with error: $code \"{$response->getReasonPhrase()}\".\n\n$response",
                $response
            );
        }

        return $response;
    }

    private function createRequest(string $source): Request
    {
        return (new Request($source, $this->options->getMethod()))
            ->withBody($this->options->getBody())
        ;
    }

    /**
     * @return AsyncHttpOptions
     */
    public function getOptions(): EncapsulatedOptions
    {
        return $this->options;
    }
}
