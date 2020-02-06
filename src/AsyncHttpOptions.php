<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Artax\Client;

/**
 * Encapsulates async HTTP client options.
 */
final class AsyncHttpOptions
{
    // Transfer timeout in milliseconds until an HTTP request is automatically aborted, use 0 to disable.
    private $transferTimeout = 15000;

    // Number of redirects to follow, or 0 to disable redirects.
    private $maxRedirects = 5;

    // Automatically add a "Referer" header on redirect.
    private $autoReferrer = true;

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

    public function getAutoReferrer(): bool
    {
        return $this->autoReferrer;
    }

    public function setAutoReferrer(bool $autoReferer): self
    {
        $this->autoReferrer = $autoReferer;

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

    public function extractArtaxOptions(): array
    {
        return [
            Client::OP_AUTO_REFERER => $this->autoReferrer,
            Client::OP_MAX_REDIRECTS => $this->maxRedirects,
            Client::OP_TRANSFER_TIMEOUT => $this->transferTimeout,
            Client::OP_MAX_BODY_BYTES => $this->maxBodyLength,
        ];
    }
}
