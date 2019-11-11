<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Net\Ssl\SslOptions;

/**
 * Encapsulates HTTP stream context options.
 *
 * @see http://php.net/manual/en/context.http.php
 */
class HttpOptions
{
    private $sslOptions;

    /** @var string|null */
    private $proxy;

    /** @var string|null */
    private $userAgent;

    private $followLocation = true;

    private $sendFullUri = false;

    private $maxRedirects = 20;

    private $protocolVersion = 1.0;

    /** @var float|null */
    private $timeout;

    public function __clone()
    {
        $this->sslOptions && $this->sslOptions = clone $this->sslOptions;
    }

    public function getSslOptions(): SslOptions
    {
        return $this->sslOptions ?? $this->sslOptions = new SslOptions;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function setProxy(?string $proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getFollowLocation(): bool
    {
        return $this->followLocation;
    }

    public function setFollowLocation(bool $followLocation): self
    {
        $this->followLocation = $followLocation;

        return $this;
    }

    public function getRequestFullUri(): bool
    {
        return $this->sendFullUri;
    }

    public function setRequestFullUri(bool $requestFullUri): self
    {
        $this->sendFullUri = $requestFullUri;

        return $this;
    }

    public function getMaxRedirects(): int
    {
        return $this->maxRedirects;
    }

    public function setMaxRedirects(int $maxRedirects): self
    {
        $this->maxRedirects = $maxRedirects;

        return $this;
    }

    public function getProtocolVersion(): float
    {
        return $this->protocolVersion;
    }

    public function setProtocolVersion(float $protocolVersion): self
    {
        $this->protocolVersion = $protocolVersion;

        return $this;
    }

    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    public function setTimeout(?float $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Extracts a list of HTTP context options only.
     *
     * @return array HTTP context options.
     */
    public function extractHttpContextOptions(): array
    {
        return array_filter([
            'user_agent' => $this->userAgent,
            'proxy' => $this->proxy,
            'request_fulluri' => $this->sendFullUri,
            'follow_location' => $this->followLocation,
            'max_redirects' => $this->maxRedirects,
            'protocol_version' => $this->protocolVersion,
            'timeout' => $this->timeout,
        ]);
    }
}
