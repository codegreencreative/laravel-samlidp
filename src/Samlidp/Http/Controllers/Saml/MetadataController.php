<?php

namespace Codegreencreative\Idp\Http\Controllers;

use App\Http\Controllers\Controller;

class MetadataController extends Controller
{
    /**
     * [getMetadata description]
     *
     * @return [type] [description]
     */
    public function index()
    {
        $certificate = trim(str_replace([
            '-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'
        ], '', file_get_contents(config('samlidp.crt'))));
        $entity_id = config('samlidp.issuer_uri');
        $login = url(config('samlidp.login_uri'));
        return view('samlidp::metadata', compact('certificate', 'entity_id', 'login'));
    }
}