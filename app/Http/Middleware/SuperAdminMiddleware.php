<?php

namespace App\Http\Middleware;

use Closure;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    //for super_admin = 2
    public function handle($request, Closure $next)
    {
        if ($request->user() && $request->user()->type != 2)
        {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
        return $next($request);
    }
}
