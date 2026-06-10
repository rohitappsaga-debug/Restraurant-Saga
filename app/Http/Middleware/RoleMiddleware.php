<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user() || !in_array($request->user()->role->value, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden - Insufficient Role'], 403);
            }

            // Flash an error message for the web interface
            session()->flash('error', 'You do not have permission to access this area.');
            
            // Redirect based on user role or back to login
            if (!$request->user()) {
                return redirect()->guest(route('login'));
            }

            return redirect()->route('home');
        }

        return $next($request);
    }
}
