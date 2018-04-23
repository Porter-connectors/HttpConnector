<?php
namespace ScriptFUSIONTest\Unit\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\Recoverable\RecoverableException;
use ScriptFUSION\Porter\Net\Http\HttpConnectionException;

final class HttpConnectionExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testRecoverable(): void
    {
        self::assertInstanceOf(RecoverableException::class, new HttpConnectionException);
    }
}
