<?php declare(strict_types=1);

namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpOptions;

/**
 * @see HttpOptions
 */
final class HttpOptionsTest extends TestCase
{
    /**
     * Tests that cloning options without making any modifications succeeds.
     */
    public function testCloneNewObject(): void
    {
        self::assertInstanceOf(HttpOptions::class, clone new HttpOptions);
    }

    /**
     * Tests that cloning options with embedded SSL options succeeds in cloning the embedded SSL options.
     */
    public function testCloneSslOptions(): void
    {
        $original = new HttpOptions;
        $ssl = $original->getSslOptions();

        self::assertNotSame($ssl, (clone $original)->getSslOptions());
    }

    public function testProxy(): void
    {
        self::assertSame($host = 'http://example.com', (new HttpOptions)->setProxy($host)->getProxy());
    }

    public function testUserAgent(): void
    {
        self::assertSame($userAgent = 'Foo/Bar', (new HttpOptions)->setUserAgent($userAgent)->getUserAgent());
    }

    public function testFollowLocation(): void
    {
        $options = new HttpOptions;

        self::assertTrue($options->setFollowLocation(true)->getFollowLocation());
        self::assertFalse($options->setFollowLocation(false)->getFollowLocation());
    }

    public function testRequestFullUri(): void
    {
        $options = new HttpOptions;

        self::assertTrue($options->setRequestFullUri(true)->getRequestFullUri());
        self::assertFalse($options->setRequestFullUri(false)->getRequestFullUri());
    }

    public function testMaxRedirects(): void
    {
        self::assertSame($maxRedirects = 10, (new HttpOptions)->setMaxRedirects($maxRedirects)->getMaxRedirects());
    }

    public function testProtocolVersion(): void
    {
        self::assertSame(
            $protocolVersion = 1.1,
            (new HttpOptions)->setProtocolVersion($protocolVersion)->getProtocolVersion()
        );
    }

    public function testTimeout(): void
    {
        self::assertSame($timeout = 20.0, (new HttpOptions)->setTimeout($timeout)->getTimeout());
    }

    public function testExtractHttpContextOptions(): void
    {
        $context = (new HttpOptions)
            ->setFollowLocation($follow = false)
            ->extractHttpContextOptions()
        ;

        self::assertSame($follow, $context['follow_location']);
    }
}
