<?php

namespace CodeGreenCreative\SamlIdp\Events;

use App\User;

use Illuminate\Queue\SerializesModels;

class UserLoggedOut
{
    use SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
