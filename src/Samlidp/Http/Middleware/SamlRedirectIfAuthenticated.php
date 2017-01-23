<?php

namespace Codegreencreative\Samlidp\Http\Middleware;

use Closure;
use App\Traits\SamlAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;


class SamlRedirectIfAuthenticated
{
    use SamlAuth;
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
        if (Auth::guard($guard)->check()) {
            return $request->has('SAMLRequest') ? response($this->samlRequest($request, Auth::user()), 200) : redirect('/');
        }

        return $next($request);
    }
}
