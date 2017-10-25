<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Options\EncapsulatedOptions;
use ScriptFUSIONTest\FixtureFactory;

final class HttpConnectorTest extends \PHPUnit_Framework_TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInvalidOptionsType()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        (new HttpConnector)->fetch(
            FixtureFactory::createConnectionContext(),
            'foo',
            \Mockery::mock(EncapsulatedOptions::class)
        );
    }

    public function testBaseUrl()
    {
        self::assertSame('foo', (new HttpConnector)->setBaseUrl('foo')->getBaseUrl());
    }
}
