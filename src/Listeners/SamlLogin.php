<?php

namespace Maghonemi\SamlIdp\Listeners;

use Maghonemi\SamlIdp\Jobs\SamlSso;
use Illuminate\Auth\Events\Login;

class SamlLogin
{
    /**
     * Listen for the Authenticated event
     *
     * @param  Authenticated $event [description]
     * @return [type]               [description]
     */
    public function handle(Login $event)
    {
        if (request()->filled('SAMLRequest') && ! request()->is('saml/logout')) {
            abort(response(SamlSso::dispatchNow()), 302);
        }
    }
}
