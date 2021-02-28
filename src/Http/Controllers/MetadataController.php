<?php

namespace Maghonemi\SamlIdp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class MetadataController extends Controller
{
    /**
     * [getMetadata description]
     *
     * @return [type] [description]
     */
    public function index()
    {
        // Check for debugbar and disable for this view
        if (class_exists('\Barryvdh\Debugbar\Facade')) {
            \Barryvdh\Debugbar\Facade::disable();
        }

        $cert = Storage::disk('samlidp')->get('cert.pem');
        $cert = preg_replace('/^\W+\w+\s+\w+\W+\s(.*)\s+\W+.*$/s', '$1', $cert);
        $cert = str_replace(PHP_EOL, "", $cert);

        return response(view('samlidp::metadata', compact('cert')), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
