<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixSocketIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $socketId = $request->header('X-Socket-ID');
        if ($socketId === 'undefined' || $socketId === 'null') {
            $request->headers->remove('X-Socket-ID');
            $request->server->remove('HTTP_X_SOCKET_ID');
        }

        $inputSocketId = $request->input('socket_id');
        if ($inputSocketId === 'undefined' || $inputSocketId === 'null') {
            $request->request->remove('socket_id');
            $request->query->remove('socket_id');
        }
        
        return $next($request);
    }
}
