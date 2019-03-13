<?php

namespace CodeGreenCreative\SamlIdp\Listeners;

use Illuminate\Auth\Events\Logout;

class SamlLogout
{
    /**
     * Upon logout, initiate SAML SLO process for each Service Provider
     *
     * @param  Logout $event
     * @return void
     */
    public function handle(Logout $event)
    {
        dd('here');
        // Need to broadcast to our other SAML apps to log out!
        // Loop through our service providers and "touch" the logout URL's
        foreach (config('samlidp.sp') as $sp) {
            if (! empty($sp['logout'])) {
                # code...
            }
        }
    }
}
