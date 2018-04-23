<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\Recoverable\RecoverableException;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSION\Porter\Net\Http\HttpServerException;

final class HttpServerExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HttpServerException
     */
    private $exception;

    private $response;

    protected function setUp(): void
    {
        $this->exception = new HttpServerException(
            'foo',
            $this->response = HttpResponse::fromPhpWrapper(['HTTP/1 123'])
        );
    }

    public function testRecoverable(): void
    {
        self::assertInstanceOf(RecoverableException::class, $this->exception);
    }

    public function testMessage(): void
    {
        $this->assertSame('foo', $this->exception->getMessage());
    }

    public function testCode(): void
    {
        $this->assertSame(123, $this->exception->getCode());
    }

    public function testBody(): void
    {
        self::assertSame($this->response, $this->exception->getResponse());
    }
}
