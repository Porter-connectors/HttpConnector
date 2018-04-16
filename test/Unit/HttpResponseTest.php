<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use ScriptFUSION\Porter\Net\Http\HttpResponse;

/**
 * @see HttpResponse
 */
final class HttpResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that headers are case insensitive when calling accessor methods.
     */
    public function testHeaderCaseSensitivity(): void
    {
        $response = HttpResponse::fromPhpWrapper(['Foo: Bar']);

        self::assertTrue($response->hasHeader('Foo'));
        self::assertTrue($response->hasHeader('foo'));

        self::assertSame(['Bar'], $response->getHeader('Foo'));
        self::assertSame(['Bar'], $response->getHeader('foo'));
    }
}
