<?php

namespace Codegreencreative\Idp;

use Illuminate\Support\Facades\Facade;

class Samlidp extends Facade
{
    /**
     * [fields description]
     *
     * @return [type] [description]
     */
    public static function fields()
    {
        if (request()->has('SAMLRequest')) {
            return sprintf('
                <input type="hidden" name="SAMLRequest" value="%s" />
                <input type="hidden" name="RelayState" value="%s" />
            ', request()->get('SAMLRequest'), request()->get('RelayState'));
        }
    }

}