<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

/** Raised when a user attempts something outside their role / data scope. Renders as HTTP 403. */
class AuthorizationException extends Exception
{
    public function __construct(string $message = 'You are not authorised to perform this action.')
    {
        parent::__construct($message, 403);
    }

    public function render(Request $request)
    {
        return response()->view('errors.403', ['exception' => $this], 403);
    }
}
