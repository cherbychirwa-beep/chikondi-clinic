<?php

namespace App\Exceptions;

use Exception;

/** The requested doctor/date/time cannot be booked (taken, outside availability, etc.). */
class SlotUnavailableException extends Exception
{
    public function __construct(string $message = 'The selected appointment slot is not available.')
    {
        parent::__construct($message);
    }
}
