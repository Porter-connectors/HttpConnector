<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\ByteStream\StreamException;
use Amp\Dns\DnsException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Client\SocketException;
use Amp\Http\Client\TimeoutException;
use Amp\Promise;
use ScriptFUSION\Porter\Connector\AsyncConnector;
use ScriptFUSION\Porter\Connector\ConnectorOptions;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;
use function Amp\call;

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

    public function fetchAsync(string $source): Promise
    {
        return call(function () use ($source): \Generator {
            $client = new DefaultClient($this->options->getCookieJar());
            $client->setOptions($this->options->extractArtaxOptions());

            try {
                /** @var Response $response */
                $response = yield $client->request($this->createRequest($source));
                $body = yield $response->getBody();
                // Retry HTTP timeouts, socket timeouts, DNS resolution, crypto negotiation and connection reset errors.
            } catch (TimeoutException|SocketException|DnsException|CryptoException|StreamException $exception) {
                // Convert exception to recoverable exception.
                throw new HttpConnectionException($exception->getMessage(), $exception->getCode(), $exception);
            }

            $response = HttpResponse::fromAmpResponse($response, $body);

            $code = $response->getStatusCode();
            if ($code < 200 || $code >= 400) {
                throw new HttpServerException(
                    // TODO: truncate response in exception message.
                    "HTTP server responded with error: $code \"{$response->getReasonPhrase()}\".\n\n$response",
                    $response
                );
            }

            return $response;
        });
    }

    private function createRequest(string $source): Request
    {
        return new Request($source, $this->options->getMethod(), $this->options->getBody());
    }

    /**
     * @return AsyncHttpOptions
     */
    public function getOptions(): EncapsulatedOptions
    {
        return $this->options;
    }
}
