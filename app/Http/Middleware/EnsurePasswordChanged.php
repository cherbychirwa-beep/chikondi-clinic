<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Forces first-login (or admin-reset) password change before any other page is usable. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            return redirect()->route('password.change')->with('warning', 'You must change your temporary password before continuing.');
        }

        return $next($request);
    }
}
