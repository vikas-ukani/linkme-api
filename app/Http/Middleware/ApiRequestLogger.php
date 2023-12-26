<?php

namespace App\Http\Middleware;

use Closure;

class ApiRequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $log = [
                'method' => $request->method(),
                'data' => $request->toArray(),
                'url' => $request->server('REQUEST_URI'),
                'headers' => $request->header(),
            ];
            \Log::info("REQUEST:: ", $log);
        } catch (\Exception $exception) {
            \Log::info("Error :: ", $exception->getMessage());
        }
        return $next($request);
    }
}
