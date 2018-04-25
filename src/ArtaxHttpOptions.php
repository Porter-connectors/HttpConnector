<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Artax\Client;
use Amp\Artax\Cookie\ArrayCookieJar;
use Amp\Artax\Cookie\CookieJar;
use Amp\Artax\RequestBody;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;

/**
 * Encapsulates Artax HTTP client options.
 */
final class ArtaxHttpOptions extends EncapsulatedOptions
{
    /**
     * @var string
     */
    private $method = 'GET';

    /**
     * @var RequestBody|null
     */
    private $body;

    /**
     * @var CookieJar
     */
    private $cookieJar;

    public function __construct()
    {
        $this->cookieJar = new ArrayCookieJar;
    }

    public function __clone()
    {
        $this->cookieJar = clone $this->cookieJar;
        $this->body && $this->body = clone $this->body;
    }

    public function setAutoEncoding(bool $autoEncoding): self
    {
        return $this->set(Client::OP_AUTO_ENCODING, $autoEncoding);
    }

    public function setTransferTimeout(int $timeout): self
    {
        return $this->set(Client::OP_TRANSFER_TIMEOUT, $timeout);
    }

    public function setMaxRedirects(int $maxRedirects): self
    {
        return $this->set(Client::OP_MAX_REDIRECTS, $maxRedirects);
    }

    public function setAutoReferrer(bool $autoReferer): self
    {
        return $this->set(Client::OP_AUTO_REFERER, $autoReferer);
    }

    public function setDiscardBody(bool $discardBody): self
    {
        return $this->set(Client::OP_DISCARD_BODY, $discardBody);
    }

    public function setDefaultHeaders(array $headers): self
    {
        return $this->set(Client::OP_DEFAULT_HEADERS, $headers);
    }

    public function setMaxHeaderBytes(int $maxHeaderBytes): self
    {
        return $this->set(Client::OP_MAX_HEADER_BYTES, $maxHeaderBytes);
    }

    public function setMaxBodyBytes(int $maxBodyBytes): self
    {
        return $this->set(Client::OP_MAX_BODY_BYTES, $maxBodyBytes);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getBody(): ?RequestBody
    {
        return $this->body;
    }

    public function setBody(?RequestBody $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }

    public function extractArtaxOptions(): array
    {
        return $this->copy();
    }
}
