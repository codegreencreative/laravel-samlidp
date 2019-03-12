<?php

namespace CodeGreenCreative\SamlIdp\Traits;

use Illuminate\Foundation\Auth\AuthenticatesUsers as LaravelAuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait AuthenticatesUsers
{
    use LaravelAuthenticatesUsers;

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        if ($request->filled('SAMLRequest')) {
            return response($this->samlRequest($request, $user), 200);
        }
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // Initiate SAML Logout
        $saml_logout = new SamlidpLogout($request, auth()->user());
        $user = User::where(config('samlidp.email_field'), $saml_logout->getRequester())->first();
        if ($user->email == auth()->user()->email) {
            $this->guard()->logout();
            $request->session()->invalidate();
            // Fire the event to log out of all SP's
            event(new UserLoggedOut($user));
        }
        return $this->loggedOut($request) ?: redirect('/');
    }
}
