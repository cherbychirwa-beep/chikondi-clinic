<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Logs the user out after a configurable period of inactivity (config chikondi.session_timeout, minutes). */
class CheckInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timeout = (int) config('chikondi.session_timeout') * 60;
            $last = (int) $request->session()->get('last_activity', time());

            if (time() - $last > $timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Your session expired due to inactivity. Please sign in again.');
            }

            $request->session()->put('last_activity', time());
        }

        return $next($request);
    }
}
