<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpOptions;

/**
 * @see HttpOptions
 */
final class HttpOptionsTest extends TestCase
{
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
}
