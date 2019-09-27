<?php

namespace CodeGreenCreative\SamlIdp\Http\Controllers;

use App\Http\Controllers\Controller;
use CodeGreenCreative\SamlIdp\Jobs\SamlSlo;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * [index description]
     * @return [type] [description]
     */
    public function index(Request $request)
    {
        // Need to broadcast to our other SAML apps to log out!
        // Loop through our service providers and "touch" the logout URL's
        foreach (config('samlidp.sp') as $key => $sp) {
            // Check if the service provider supports SLO
            if (! empty($sp['logout']) && ! in_array($key, $request->session()->get('saml.slo', []))) {
                // Push this SP onto the saml slo array
                $request->session()->push('saml.slo', $key);
                return redirect(SamlSlo::dispatchNow($sp));
            }
        }

        $request->session()->forget('saml.slo');

        if (config('samlidp.logout_after_slo')) {
            $request->session()->flush();
            $request->session()->regenerate();
        }

        return redirect(config('samlidp.login_uri'));
    }
}
