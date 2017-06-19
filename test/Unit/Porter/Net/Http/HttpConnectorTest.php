<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpOptions;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;

final class HttpConnectorTest extends \PHPUnit_Framework_TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOptions()
    {
        self::assertSame($options = new HttpOptions, (new HttpConnector($options))->getOptions());
    }

    public function testInvalidOptionsType()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        (new HttpConnector)->fetch('foo', \Mockery::mock(EncapsulatedOptions::class));
    }

    public function testBaseUrl()
    {
        self::assertSame('foo', (new HttpConnector)->setBaseUrl('foo')->getBaseUrl());
    }
}
