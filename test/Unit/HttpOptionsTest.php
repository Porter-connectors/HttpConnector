<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpOptions;

/**
 * @see HttpOptions
 */
final class HttpOptionsTest extends TestCase
{
    /**
     * Tests that when no protocol versions are set, none are reported, deferring to the client default.
     */
    public function testDefaultProtocolVersions(): void
    {
        self::assertSame([], (new HttpOptions)->getProtocolVersions());
    }

    /**
     * Tests that when protocol versions are set, the same versions are reported.
     */
    public function testSetProtocolVersions(): void
    {
        self::assertSame(
            $versions = ['1.1'],
            (new HttpOptions)->setProtocolVersions($versions)->getProtocolVersions(),
        );
    }
}
