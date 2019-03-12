<?php

namespace CodeGreenCreative\SamlIdp\Http\Middleware;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use CodeGreenCreative\SamlIdp\Traits\SamlidpAuth;

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
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check() && $request->has('SAMLRequest') && ! $request->is('saml/logout')) {
            return response($this->samlRequest($request, Auth::user()), 200);
        }

        if (Auth::guard($guard)->check() && ! $request->is('saml/logout')) {
            return redirect('/');
        }

        return $next($request);
    }
}
