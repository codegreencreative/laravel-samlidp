<?php

namespace Codegreencreative\Idp\Http\Controllers;

use App\Http\Controllers\Controller;
use Codegreencreative\Idp\Traits\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * [__constructor description]
     *
     * @return [type] [description]
     */
    public function __construct()
    {
        $this->middleware(['web', 'saml']);
    }

}