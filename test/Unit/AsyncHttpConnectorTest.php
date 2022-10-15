<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\AsyncHttpConnector;

/**
 * @see AsyncHttpConnector
 */
final class AsyncHttpConnectorTest extends TestCase
{
    /**
     * Tests that when the connector is cloned, all properties are cloned except the pool.
     */
    public function testClone(): void
    {
        $connector = new AsyncHttpConnector();
        $newConnector = clone $connector;

        self::assertNotSame($connector->getOptions(), $newConnector->getOptions(), 'Options cloned.');
        self::assertNotSame($connector->getCookieJar(), $newConnector->getCookieJar(), 'Cookie jar cloned.');

        $poolProperty = new \ReflectionProperty(AsyncHttpConnector::class, 'pool');

        self::assertSame(
            $poolProperty->getValue($connector),
            $poolProperty->getValue($newConnector),
            'Pool not cloned.'
        );
    }
}
