<?php

namespace CodeGreenCreative\SamlIdp\Http\Controllers;

use App\Http\Controllers\Controller;
use CodeGreenCreative\SamlIdp\Jobs\SamlSlo;
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
        $sloRedirect = $request->session()->get('saml.sloRedirect');
        if (!$sloRedirect) {
            $this->setSloRedirect($request);
            $sloRedirect = $request->session()->get('saml.sloRedirect');
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

        $request->session()->forget('saml.slo');
        $request->session()->forget('saml.sloRedirect');

        if (config('samlidp.logout_after_slo')) {
            $request->session()->flush();
            $request->session()->regenerate();
        }

        return redirect($sloRedirect);
    }

    private function setSloRedirect(Request $request)
    {
        $httpReferer = $request->server('HTTP_REFERER');
        $redirects = config('samlidp.spSloRedirects', []);
        $sloRedirect = config('samlidp.login_uri');
        foreach ($redirects as $referer => $redirectPath) {
            if (Str::startsWith($httpReferer, $referer)) {
                $sloRedirect = $redirectPath;
                break;
            }
        }

        $request->session()->put('saml.sloRedirect', $sloRedirect);
    }
}
