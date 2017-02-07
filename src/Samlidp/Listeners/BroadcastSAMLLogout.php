<?php

namespace Codegreencreative\Idp\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Codegreencreative\Idp\Events\UserLoggedOut;

class BroadcastSAMLLogout implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * [handle description]
     *
     * @param  BroadcastSAMLLogout $event [description]
     * @return [type]                     [description]
     */
    public function handle(UserLoggedOut $event)
    {
        // Need to broadcast to our other SAML apps to log out!
        // Loop through our service providers and "touch" the logout URL's
        foreach (config('samlidp.sp') as $sp) {
            if ( ! empty($sp['logout'])) {
                # code...
            }
        }
        // dd($event->user);
    }
}


