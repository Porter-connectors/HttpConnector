<?php
declare(strict_types=1);

namespace ScriptFUSIONTest;

use Amp\ByteStream\ReadableStream;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use ScriptFUSION\StaticClass;

final class FixtureFactory
{
    use StaticClass;

    public static function createResponse(
        string $protocolVersion = '1.0',
        int $status = 200,
        ?string $reason = 'OK',
        array $headers = [],
        ReadableStream $body = null,
        Request $request = null,
    ): Response {
        return new Response(
            $protocolVersion,
            $status,
            $reason,
            $headers,
            $body ?? \Mockery::spy(ReadableStream::class),
            $request ?? new Request('')
        );
    }
}
