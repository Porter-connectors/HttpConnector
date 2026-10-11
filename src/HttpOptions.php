<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

/**
 * Encapsulates HTTP client options.
 */
final class HttpOptions
{
    // True to return error responses, false to throw HttpServerException instead.
    private bool $returnErrors = false;

    // Transfer timeout in milliseconds until an HTTP request is automatically aborted, use 0 to disable.
    private int $transferTimeout = 15_000;

    // Number of redirects to follow, or 0 to disable redirects.
    private int $maxRedirects = 5;

    // Maximum body length in bytes. Default 10MiB.
    private int $maxBodyLength = 0x100_000 * 10;

    // HTTP protocol versions to negotiate, in preference order. Default is the client's choice.
    private array $protocolVersions = [];

    public function isReturningErrors(): bool
    {
        return $this->returnErrors;
    }

    public function willReturnErrors(bool $returnErrors = true): HttpOptions
    {
        $this->returnErrors = $returnErrors;

        return $this;
    }

    public function getTransferTimeout(): int
    {
        return $this->transferTimeout;
    }

    public function setTransferTimeout(int $transferTimeout): self
    {
        $this->transferTimeout = $transferTimeout;

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

    /**
     * Gets the maximum body length.
     *
     * @return int Body length in bytes.
     */
    public function getMaxBodyLength(): int
    {
        return $this->maxBodyLength;
    }

    /**
     * Sets the maximum body length.
     *
     * @param int $maxBodyLength Body length in bytes.
     */
    public function setMaxBodyLength(int $maxBodyLength): self
    {
        $this->maxBodyLength = $maxBodyLength;

        return $this;
    }

    /**
     * Gets the HTTP protocol versions to negotiate, in preference order.
     *
     * @return list<string> Protocol versions, or an empty list for the client's default.
     */
    public function getProtocolVersions(): array
    {
        return $this->protocolVersions;
    }

    /**
     * Sets the HTTP protocol versions to negotiate, in preference order.
     *
     * Passing an empty list restores the HTTP client's default.
     *
     * @param list<string> $protocolVersions Protocol versions, e.g. ["1.0", "1.1", "2"].
     */
    public function setProtocolVersions(array $protocolVersions): self
    {
        $this->protocolVersions = $protocolVersions;

        return $this;
    }
}
