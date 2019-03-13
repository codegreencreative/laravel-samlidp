<?php

namespace CodeGreenCreative\SamlIdp\Listeners;

use CodeGreenCreative\SamlIdp\Traits\SamlidpAuth;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Http\Response;

class SamlRequest
{
    use SamlidpAuth;

    /**
     * Listen for the Authenticated event
     *
     * @param  Authenticated $event [description]
     * @return [type]               [description]
     */
    public function handle(Authenticated $event)
    {
        if (request()->filled('SAMLRequest') && ! request()->is('saml/logout')) {
            abort(response($this->samlRequest(request(), auth()->user()), 200));
        }
    }
}
