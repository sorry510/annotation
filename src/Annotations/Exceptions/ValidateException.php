<?php

namespace Sorry510\Annotations\Exceptions;

use RuntimeException;

class ValidateException extends RuntimeException
{
    private $statusCode;
    private $headers;

    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json([
            "code" => $this->getStatusCode(), // 自定义code码
            "message" => $this->getMessage(),
            "data" => null,
            "timestamp" => ceil(microtime(true) * 1000), // 毫秒
        ], $this->getStatusCode(), [], JSON_UNESCAPED_UNICODE);
    }
}
