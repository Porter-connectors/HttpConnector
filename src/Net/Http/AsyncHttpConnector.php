<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
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
            $client = new DefaultClient;
            $client->setOptions($this->getOptions()->extractArtaxOptions());

            return $context->retry(static function () use ($client, $source) {
                /** @var Response $response */
                $response = yield $client->request($source);
                $body = yield $response->getBody();

                return HttpResponse::fromArtaxResponse($response, $body);
            });
        });
    }

    public function getOptions()
    {
        return $this->options;
    }
}
