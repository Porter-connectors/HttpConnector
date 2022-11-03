<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Unit;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Connector\Recoverable\RecoverableException;
use ScriptFUSION\Porter\Net\Http\HttpResponse;
use ScriptFUSION\Porter\Net\Http\HttpServerException;
use ScriptFUSIONTest\FixtureFactory;

final class HttpServerExceptionTest extends TestCase
{
    private HttpServerException $exception;

    private HttpResponse $response;

    protected function setUp(): void
    {
        $this->exception = new HttpServerException(
            'foo',
            $this->response = new HttpResponse(FixtureFactory::createResponse(status: 123))
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
