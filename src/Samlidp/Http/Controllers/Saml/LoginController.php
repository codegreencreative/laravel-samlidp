<?php

namespace CodeGreenCreative\SamlIdp\Http\Controllers;

use App\Http\Controllers\Controller;
use CodeGreenCreative\SamlIdp\Traits\AuthenticatesUsers;

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