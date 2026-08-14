<?php

namespace Tualo\ConnectAI\Exception;

use Exception;

class ConnectException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}