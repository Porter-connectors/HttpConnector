<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\ByteStream\StreamException;
use Amp\Dns\DnsException;
use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\Cookie\InMemoryCookieJar;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Client\SocketException;
use Amp\Http\Client\TimeoutException;
use Amp\Promise;
use ScriptFUSION\Porter\Connector\AsyncConnector;
use ScriptFUSION\Porter\Connector\AsyncDataSource;
use function Amp\call;

class AsyncHttpConnector implements AsyncConnector
{
    private $options;

    private $cookieJar;

    public function __construct(AsyncHttpOptions $options = null, CookieJar $cookieJar = null)
    {
        $this->options = $options ?: new AsyncHttpOptions;
        $this->cookieJar = $cookieJar ?: new InMemoryCookieJar;
    }

    public function __clone()
    {
        $this->options = clone $this->options;
        $this->cookieJar = clone $this->cookieJar;
    }

    public function fetchAsync(AsyncDataSource $source): Promise
    {
        return call(function () use ($source): \Generator {
            if (!$source instanceof AsyncHttpDataSource) {
                throw new \InvalidArgumentException('Source must be of type: AsyncHttpDataSource.');
            }

            $client = new DefaultClient($this->cookieJar);
            $client->setOptions($this->options->extractArtaxOptions());

            try {
                /** @var Response $response */
                $response = yield $client->request($this->createRequest($source));
                $body = yield $response->getBody();
                // Retry HTTP timeouts, socket timeouts, DNS resolution and connection reset errors.
            } catch (TimeoutException|SocketException|DnsException|StreamException $exception) {
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

    private function createRequest(AsyncHttpDataSource $source): Request
    {
        $request = new Request($source->getUrl(), $source->getMethod(), $source->getBody());
        $request->setHeaders($source->getHeaders());

        return $request;
    }

    public function getOptions(): AsyncHttpOptions
    {
        return $this->options;
    }

    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }
}
