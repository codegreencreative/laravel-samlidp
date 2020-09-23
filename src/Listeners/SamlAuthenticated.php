<?php

namespace CodeGreenCreative\SamlIdp\Listeners;

use CodeGreenCreative\SamlIdp\Jobs\SamlSso;
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
        if (request()->filled('SAMLRequest') && ! request()->is('saml/logout') && request()->isMethod('get')) {
            abort(response(SamlSso::dispatchNow()), 302);
        }
    }
}
