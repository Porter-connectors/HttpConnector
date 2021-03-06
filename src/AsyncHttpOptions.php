<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

/**
 * Encapsulates async HTTP client options.
 */
final class AsyncHttpOptions
{
    // Transfer timeout in milliseconds until an HTTP request is automatically aborted, use 0 to disable.
    private $transferTimeout = 15000;

    // Number of redirects to follow, or 0 to disable redirects.
    private $maxRedirects = 5;

    // Maximum body length in bytes. Default 10MiB.
    private $maxBodyLength = 0x100000 * 10;

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

    public function getMaxBodyLength(): int
    {
        return $this->maxBodyLength;
    }

    public function setMaxBodyLength($maxBodyLength): self
    {
        $this->maxBodyLength = $maxBodyLength;

        return $this;
    }
}
