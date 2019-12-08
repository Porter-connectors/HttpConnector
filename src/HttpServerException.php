<?php
declare(strict_types=1);

namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\Recoverable\RecoverableException;

/**
 * The exception that is thrown when the server responds with an error code.
 */
class HttpServerException extends \RuntimeException implements RecoverableException
{
    /**
     * @var HttpResponse Response.
     */
    private $response;

    /**
     * Initializes this instance with the specified HTTP error message and response.
     *
     * @param string $message HTTP error message.
     * @param HttpResponse $response Response.
     */
    public function __construct($message, HttpResponse $response)
    {
        parent::__construct($message, $response->getStatusCode());

        $this->response = $response;
    }

    /**
     * Gets the response.
     *
     * @return HttpResponse Response.
     */
    public function getResponse()
    {
        return $this->response;
    }
}
