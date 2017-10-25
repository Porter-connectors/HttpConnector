<?php
namespace ScriptFUSIONTest;

use ScriptFUSION\Porter\Connector\ConnectionContext;
use ScriptFUSION\StaticClass;

final class FixtureFactory
{
    use StaticClass;

    /**
     * Builds ConnectionContexts with sane defaults for testing.
     *
     * @return ConnectionContext
     */
    public static function createConnectionContext()
    {
        return new ConnectionContext(
            false,
            function () {
                // Intentionally empty.
            },
            1
        );
    }
}
