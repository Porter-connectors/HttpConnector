<?php
namespace ScriptFUSION\Porter\Net\Http;

/**
 * The exception that is thrown when an interface method is not implemented.
 */
final class NotImplementedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Method not implemented.');
    }
}
