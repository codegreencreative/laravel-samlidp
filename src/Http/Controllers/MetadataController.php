<?php

namespace CodeGreenCreative\SamlIdp\Http\Controllers;

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

        return response(view('samlidp::metadata'), 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
