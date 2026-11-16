<?php

namespace App\Exceptions;

use Exception;

/** Business-rule validation failure raised by the service layer (not Laravel's request validation). */
class ValidationException extends Exception
{
    /** @param array<string,string> $errors field => message */
    public function __construct(string $message = 'The submitted data is not valid.', protected array $errors = [])
    {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
