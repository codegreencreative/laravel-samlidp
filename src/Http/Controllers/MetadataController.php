<?php

namespace CodeGreenCreative\SamlIdp\Http\Controllers;

use Illuminate\Routing\Controller;
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

        $cert = config('samlidp.cert') ?: Storage::disk('samlidp')->get(config('samlidp.certname', 'cert.pem'));

        if (strpos($cert, 'file://') === 0) {
            if (!is_file($cert)) {
                throw new \InvalidArgumentException(sprintf("File not found '%s'", $cert));
            }
            $cert = file_get_contents($cert);
        }

        $cert = preg_replace('/^\W+\w+\s+\w+\W+\s(.*)\s+\W+.*$/s', '$1', trim($cert));
        $cert = str_replace(PHP_EOL, "", $cert);

        return response(view('samlidp::metadata', compact('cert')), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
