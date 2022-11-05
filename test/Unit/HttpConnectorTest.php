<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpOptions;

/**
 * @see HttpConnector
 */
final class HttpConnectorTest extends TestCase
{
    /**
     * Tests that the options passed to the constructor are the same returned from the accessor method.
     */
    public function testOptions(): void
    {
        self::assertSame($options = new HttpOptions(), (new HttpConnector($options))->getOptions());
    }

    /**
     * Tests that when the connector is cloned, all properties are cloned except the pool.
     */
    public function testClone(): void
    {
        $connector = new HttpConnector();
        $newConnector = clone $connector;

        self::assertNotSame($connector->getOptions(), $newConnector->getOptions(), 'Options cloned.');
        self::assertNotSame($connector->getCookieJar(), $newConnector->getCookieJar(), 'Cookie jar cloned.');

        $poolProperty = new \ReflectionProperty(HttpConnector::class, 'pool');

        self::assertSame(
            $poolProperty->getValue($connector),
            $poolProperty->getValue($newConnector),
            'Pool not cloned.'
        );
    }
}
