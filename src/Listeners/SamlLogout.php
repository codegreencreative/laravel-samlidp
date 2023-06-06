<?php

namespace CodeGreenCreative\SamlIdp\Listeners;

use Illuminate\Auth\Events\Logout;

class SamlLogout
{
    /**
     * Upon logout, initiate SAML SLO process for each Service Provider
     * Simply redirect to the saml/logout route to handle SLO
     *
     * @param  Logout $event
     * @return void
     */
    public function handle(Logout $event)
    {
        // Make sure we are not in the process of SLO when handling the redirect
        if (in_array($event->guard, config('samlidp.guards')) && null === session('saml.slo')) {
            abort(redirect('saml/logout'), 200);
        }
    }
}
