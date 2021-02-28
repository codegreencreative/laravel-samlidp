<?php

namespace Maghonemi\SamlIdp\Listeners;

use Maghonemi\SamlIdp\Jobs\SamlSso;
use Illuminate\Auth\Events\Authenticated;

class SamlAuthenticated
{
    /**
     * Listen for the Authenticated event
     *
     * @param  Authenticated $event [description]
     * @return [type]               [description]
     */
    public function handle(Authenticated $event)
    {
        if (request()->filled('SAMLRequest') && ! request()->is('saml/logout')) {
            abort(response(SamlSso::dispatchNow()), 302);
        }
    }
}
