<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Exceptions;

use Throwable;

class HttpClientServerException extends BakkuClientApiException
{
    public function __construct(string $message = "A server error occurred (5xx) while making the HTTP request.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
