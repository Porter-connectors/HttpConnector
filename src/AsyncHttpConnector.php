<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Artax\DefaultClient;
use Amp\Artax\DnsException;
use Amp\Artax\Response;
use Amp\Artax\SocketException;
use Amp\Artax\TimeoutException;
use Amp\Promise;
use ScriptFUSION\Porter\Connector\AsyncConnector;
use ScriptFUSION\Porter\Connector\ConnectionContext;
use ScriptFUSION\Porter\Connector\ConnectorOptions;

class AsyncHttpConnector implements AsyncConnector, ConnectorOptions
{
    private $options;

    public function __construct(ArtaxHttpOptions $options = null)
    {
        $this->options = $options ?: new ArtaxHttpOptions;
    }

    public function __clone()
    {
        $this->options = clone $this->options;
    }

    public function fetchAsync(ConnectionContext $context, string $source): Promise
    {
        return \Amp\call(function () use ($context, $source) {
            $client = new DefaultClient($this->getOptions()->getCookieJar());
            $client->setOptions($this->getOptions()->extractArtaxOptions());

            return $context->retryAsync(
                static function () use ($client, $source) {
                    try {
                        /** @var Response $response */
                        $response = yield $client->request($source);
                        // Retry HTTP timeouts, socket timeouts and DNS resolution errors.
                    } catch (TimeoutException | SocketException | DnsException $exception) {
                        // Convert exception to recoverable exception.
                        throw new HttpConnectionException($exception->getMessage(), $exception->getCode(), $exception);
                    }

                    $body = yield $response->getBody();

                    return HttpResponse::fromArtaxResponse($response, $body);
                }
            );
        });
    }

    public function getOptions()
    {
        return $this->options;
    }
}
