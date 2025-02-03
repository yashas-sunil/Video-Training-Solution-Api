<?php

namespace App\Http\Middleware;

use Closure;

class VerifyApiKey
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
        $key = request()->header('X-Api-Key');

        if ($key != env('API_KEY')) {
            abort(401);
        }

        return $next($request);
    }
}
