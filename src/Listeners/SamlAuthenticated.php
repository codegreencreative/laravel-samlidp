<?php

namespace CodeGreenCreative\SamlIdp\Listeners;

use Illuminate\Auth\Events\Authenticated;
use CodeGreenCreative\SamlIdp\Jobs\SamlSso;

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
        if (
            in_array($event->guard, config('samlidp.guards')) &&
            request()->filled('SAMLRequest') &&
            !request()->is('saml/logout') &&
            request()->isMethod('get')
        ) {
            abort(response(SamlSso::dispatchSync($event->guard)), 302);
        }
    }
}
