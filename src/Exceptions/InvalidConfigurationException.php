<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Exceptions;

use Exception;
use Throwable;

class InvalidConfigurationException extends BakkuClientApiException
{
    public function __construct(string $message = "Invalid Bakku Client configuration.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
