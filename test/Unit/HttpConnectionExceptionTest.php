<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Porter\Connector\Recoverable\RecoverableException;
use ScriptFUSION\Porter\Net\Http\HttpConnectionException;

final class HttpConnectionExceptionTest extends TestCase
{
    public function testRecoverable(): void
    {
        self::assertInstanceOf(RecoverableException::class, new HttpConnectionException);
    }
}
