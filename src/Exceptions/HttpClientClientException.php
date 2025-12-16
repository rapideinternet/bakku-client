<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Exceptions;

use Throwable;

class HttpClientClientException extends BakkuClientApiException
{
    public function __construct(string $message = "A client error occurred (4xx) while making the HTTP request.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
