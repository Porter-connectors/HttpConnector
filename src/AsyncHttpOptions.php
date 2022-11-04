<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

/**
 * Encapsulates async HTTP client options.
 */
final class AsyncHttpOptions
{
    // Transfer timeout in milliseconds until an HTTP request is automatically aborted, use 0 to disable.
    private int $transferTimeout = 15_000;

    // Number of redirects to follow, or 0 to disable redirects.
    private int $maxRedirects = 5;

    // Maximum body length in bytes. Default 10MiB.
    private int $maxBodyLength = 0x100_000 * 10;

    private ?string $certificateAuthorityFilePath = null;

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

    public function getCertificateAuthorityFilePath(): ?string
    {
        return $this->certificateAuthorityFilePath;
    }

    public function setCertificateAuthorityFilePath(string $path): self
    {
        $this->certificateAuthorityFilePath = $path;

        return $this;
    }
}
