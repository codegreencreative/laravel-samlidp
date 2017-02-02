<?php

namespace Codegreencreative\Idp\Listeners;

use Codegreencreative\Idp\Events\UserLoggedOut;

class BroadcastSAMLLogout
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
        // dd($event->user);
    }
}


