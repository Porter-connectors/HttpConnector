<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;

/**
 * Encapsulates TLS socket options.
 */
final class TlsOptions
{
    private ?string $peerName = null;

    private bool $verifyPeer = true;

    private ?string $certificateAuthorityFilePath = null;

    private ?string $certificateAuthorityDirectory = null;

    private ?Certificate $certificate = null;

    private int $verificationDepth = 10;

    private ?string $ciphers = null;

    private bool $capturePeer = false;

    private bool $serverNameIndication = true;

    public function getPeerName(): ?string
    {
        return $this->peerName;
    }

    public function setPeerName(?string $peerName): self
    {
        $this->peerName = $peerName;

        return $this;
    }

    public function willVerifyPeer(): bool
    {
        return $this->verifyPeer;
    }

    public function setVerifyPeer(bool $verifyPeer): self
    {
        $this->verifyPeer = $verifyPeer;

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

    public function getCertificateAuthorityDirectory(): ?string
    {
        return $this->certificateAuthorityDirectory;
    }

    public function setCertificateAuthorityDirectory(?string $certificateAuthorityDirectory): self
    {
        $this->certificateAuthorityDirectory = $certificateAuthorityDirectory;

        return $this;
    }

    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    public function setCertificate(?Certificate $certificate): self
    {
        $this->certificate = $certificate;

        return $this;
    }

    public function getVerificationDepth(): int
    {
        return $this->verificationDepth;
    }

    public function setVerificationDepth(int $verificationDepth): self
    {
        $this->verificationDepth = $verificationDepth;

        return $this;
    }

    public function getCiphers(): ?string
    {
        return $this->ciphers;
    }

    public function setCiphers(?string $ciphers): self
    {
        $this->ciphers = $ciphers;

        return $this;
    }

    public function willCapturePeer(): bool
    {
        return $this->capturePeer;
    }

    public function setCapturePeer(bool $capturePeer): self
    {
        $this->capturePeer = $capturePeer;

        return $this;
    }

    public function isServerNameIndicationEnabled(): bool
    {
        return $this->serverNameIndication;
    }

    public function setServerNameIndication(bool $serverNameIndication): self
    {
        $this->serverNameIndication = $serverNameIndication;

        return $this;
    }

    public function toAmpContext(): ClientTlsContext
    {
        $context = (new ClientTlsContext($this->getPeerName() ?? ''))
            ->withCaFile($this->getCertificateAuthorityFilePath())
            ->withCaPath($this->getCertificateAuthorityDirectory())
            ->withCertificate($this->getCertificate())
            ->withVerificationDepth($this->getVerificationDepth())
            ->withCiphers($this->getCiphers())
        ;

        $context = $this->willVerifyPeer() ? $context->withPeerVerification() : $context->withoutPeerVerification();
        $context = $this->willCapturePeer() ? $context->withPeerCapturing() : $context->withoutPeerCapturing();
        $context = $this->isServerNameIndicationEnabled() ? $context->withSni() : $context->withoutSni();

        return $context;
    }
}
