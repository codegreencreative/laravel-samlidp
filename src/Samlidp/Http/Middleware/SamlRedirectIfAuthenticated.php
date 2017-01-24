<?php

namespace Codegreencreative\Idp\Http\Middleware;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Codegreencreative\Idp\Traits\SamlidpAuth;


class SamlRedirectIfAuthenticated
{
    use SamlidpAuth;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && $request->has('SAMLRequest')) {
            return response($this->samlRequest($request, Auth::user()), 200);
        }

        return $next($request);
    }
}
