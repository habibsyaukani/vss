<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FleetManagerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (!in_array(auth()->user()->role, ['admin', 'fleet_manager'])) {
            abort(403, 'Unauthorized. Fleet Manager or Admin access required.');
        }

        if (auth()->user()->status !== 'active') {
            auth()->logout();
            return redirect('/login')->with('error', 'Your account is inactive.');
        }

        return $next($request);
    }
}
