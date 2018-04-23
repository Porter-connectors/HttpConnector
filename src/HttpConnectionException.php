<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\Recoverable\RecoverableException;

/**
 * The exception that is thrown when an HTTP connection error occurs.
 */
class HttpConnectionException extends \RuntimeException implements RecoverableException
{
    // Intentionally empty.
}
