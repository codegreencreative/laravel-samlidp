<?php

namespace Maghonemi\SamlIdp\Http\Controllers;

use App\Http\Controllers\Controller;
use Maghonemi\SamlIdp\Jobs\SamlSlo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LogoutController extends Controller
{
    /**
     * [index description]
     * @return [type] [description]
     */
    public function index(Request $request)
    {
        $slo_redirect = $request->session()->get('saml.slo_redirect');
        if (!$slo_redirect) {
            $this->setSloRedirect($request);
            $slo_redirect = $request->session()->get('saml.slo_redirect');
        }

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

        if (config('samlidp.logout_after_slo')) {
            auth()->logout();
        }

        $request->session()->forget('saml.slo');
        $request->session()->forget('saml.slo_redirect');

        return redirect($slo_redirect);
    }

    private function setSloRedirect(Request $request)
    {
        // Look for return_to query in case of not relying on HTTP_REFERER
        $http_referer = $request->has('return_to') ? $request->get('return_to') : $request->server('HTTP_REFERER');
        $redirects = config('samlidp.sp_slo_redirects', []);
        $slo_redirect = config('samlidp.login_uri');
        foreach ($redirects as $referer => $redirectPath) {
            if (Str::startsWith($http_referer, $referer)) {
                $slo_redirect = $redirectPath;
                break;
            }
        }

        $request->session()->put('saml.slo_redirect', $slo_redirect);
    }
}
