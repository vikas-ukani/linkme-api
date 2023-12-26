<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ...$guards)
    {
        if (in_array('auth:api', $request->route()->action['middleware'])) {
            $request->headers->set('authorization', ['Bearer ' . $request->input('accessToken')]);
        }

        $this->authenticate($request, $guards);
        return $next($request);
    }
}
