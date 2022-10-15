<?php declare(strict_types=1);

namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpOptions;

/**
 * @see HttpConnector
 */
final class HttpConnectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Tests that the options passed to the constructor are the same returned from the accessor method.
     */
    public function testOptions(): void
    {
        self::assertSame($options = new HttpOptions, (new HttpConnector($options))->getOptions());
    }
}
