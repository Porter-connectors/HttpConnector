<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpOptions;

final class HttpConnectorTest extends \PHPUnit_Framework_TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Tests that the options passed to the constructor are the same returned from the accessor method.
     */
    public function testOptions()
    {
        self::assertSame($options = new HttpOptions, (new HttpConnector($options))->getOptions());
    }
}
