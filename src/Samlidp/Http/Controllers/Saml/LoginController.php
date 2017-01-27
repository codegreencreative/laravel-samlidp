<?php

namespace Codegreencreative\Idp\Http\Controllers;

use App\Http\Controllers\Controller;
use Codegreencreative\Idp\Traits\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

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