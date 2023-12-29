<?php

namespace CodeGreenCreative\SamlIdp\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use CodeGreenCreative\SamlIdp\Jobs\SamlSlo;

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

        if (null === $request->session()->get('saml.slo')) {
            $request->session()->put('saml.slo', []);
        }

        if (config('samlidp.service_provider_model_usage')) {
            $spModelClass = config('samlidp.service_provider_model');
            $serviceProviders = $spModelClass::all();

            foreach ($serviceProviders as $serviceProvider) {
                $spConfiguration = [
                    'destination' => $serviceProvider->destination_url,
                    'logout' => $serviceProvider->logout_url,
                    'certificate' => $serviceProvider->certificate,
                    'query_params' => $serviceProvider->query_parameters,
                    'encrypt_assertion' => $serviceProvider->encrypt_assertion,
                    'block_encryption_algorithm' => $serviceProvider->block_encryption_algorithm,
                    'key_transport_encryption' => $serviceProvider->key_transport_encryption
                ];

                $spConfigs = config('samlidp.sp');
                $spConfigs[base64_encode($serviceProvider->destination_url)] = $spConfiguration;

                config(['samlidp.sp' => $spConfigs]);
            }
        }

        // Need to broadcast to our other SAML apps to log out!
        // Loop through our service providers and "touch" the logout URL's
        foreach (config('samlidp.sp') as $key => $sp) {
            // Check if the service provider supports SLO
            if (!empty($sp['logout']) && !in_array($key, $request->session()->get('saml.slo', []))) {
                // Push this SP onto the saml slo array
                $request->session()->push('saml.slo', $key);
                return redirect(SamlSlo::dispatchSync($sp));
            }
        }

        if (config('samlidp.logout_after_slo')) {
            auth()->logout();
            $request->session()->invalidate();
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
