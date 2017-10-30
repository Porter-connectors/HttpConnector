<?php
namespace ScriptFUSION\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\RecoverableConnectorException;

/**
 * The exception that is thrown when the server responds with an error code.
 */
class HttpServerException extends RecoverableConnectorException
{
    /**
     * @var HttpResponse Response.
     */
    private $response;

    /**
     * Initializes this instance with the specified HTTP error message, HTTP response code and response.
     *
     * @param string $message HTTP error message.
     * @param int $code HTP response code.
     * @param HttpResponse $response Response.
     */
    public function __construct($message, $code, HttpResponse $response)
    {
        parent::__construct($message, $code);

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
