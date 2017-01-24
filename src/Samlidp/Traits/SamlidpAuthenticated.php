<?php

namespace Codegreencreateive\Idp\Traits;

use Illuminate\Http\Request;
use Codegreencreative\Idp\Traits\SamlidpAuth;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Lang;
// use Illuminate\Foundation\Auth\ThrottlesLogins;
// use Illuminate\Foundation\Auth\RedirectsUsers;

trait SamlidpAuthenticated
{
    use SamlidpAuth;

    /**
     * The user has been authenticated.
     *
     * @param  Request $request [description]
     * @param  [type]  $user    [description]
     * @return [type]           [description]
     */
    public function authenticated(Request $request, $user)
    {
        if ($request->has('SAMLRequest')) {
            return $this->samlRequest($request, $user);
        }
    }

}
