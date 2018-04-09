<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\RecoverableConnectorException;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSION\Porter\Net\Http\HttpServerException;

final class HttpServerExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpServerException
     */
    private $exception;

    private $response;

    protected function setUp()
    {
        $this->exception = new HttpServerException(
            'foo',
            $this->response = new HttpResponse(['HTTP/1 123'])
        );
    }

    public function testRecoverable()
    {
        self::assertInstanceOf(RecoverableConnectorException::class, $this->exception);
    }

    public function testMessage()
    {
        $this->assertSame('foo', $this->exception->getMessage());
    }

    public function testCode()
    {
        $this->assertSame(123, $this->exception->getCode());
    }

    public function testBody()
    {
        self::assertSame($this->response, $this->exception->getResponse());
    }
}
