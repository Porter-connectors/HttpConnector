<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSIONTest\FixtureFactory;

/**
 * @see HttpResponse
 */
final class HttpResponseTest extends TestCase
{
    /**
     * Tests that headers are case-insensitive when calling accessor methods.
     */
    public function testHeaderCaseSensitivity(): void
    {
        $response = new HttpResponse(FixtureFactory::createResponse(headers: [$name = 'Alfa' => $value = 'Beta']));

        self::assertTrue($response->hasHeader($name));
        self::assertTrue($response->hasHeader(strtolower($name)));

        self::assertSame([$value], $response->getHeader($name));
        self::assertSame([$value], $response->getHeader(strtolower($name)));
    }
}
