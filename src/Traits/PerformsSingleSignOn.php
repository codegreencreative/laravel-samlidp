<?php

namespace Maghonemi\SamlIdp\Traits;

use Illuminate\Support\Facades\Storage;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use RobRichards\XMLSecLibs\XMLSecurityKey;

trait PerformsSingleSignOn
{
    private $issuer;
    private $certificate;
    private $private_key;
    private $request;
    private $response;

    /**
     * [__construct description]
     */
    protected function init()
    {
        $this->issuer = url(config('samlidp.issuer_uri'));
        $this->certificate = (new X509Certificate)->loadPem(Storage::disk('samlidp')->get('cert.pem'));
        $this->private_key = Storage::disk('samlidp')->get('key.pem');
        $this->private_key = KeyHelper::createPrivateKey($this->private_key, '', false, XMLSecurityKey::RSA_SHA256);
    }

    /**
     * Send a SAML response/request
     *
     * @param  string $binding_type
     * @param  string $as
     * @return string Target URL
     */
    protected function send($binding_type, $as = 'asResponse')
    {
        // The response will be to the sls URL of the SP
        $bindingFactory = new BindingFactory;
        $binding = $bindingFactory->create($binding_type);
        $messageContext = new MessageContext();
        $messageContext->setMessage($this->response)->$as();
        $message = $messageContext->getMessage();
        if (! empty(request()->filled('RelayState'))) {
            $message->setRelayState(request('RelayState'));
        }
        $httpResponse = $binding->send($messageContext);
        // Just return the target URL for proper redirection
        return $httpResponse->getTargetUrl();
    }

    /**
     * Get service provider from AuthNRequest
     *
     * @return string
     */
    public function getServiceProvider($request)
    {
        return base64_encode($request->getAssertionConsumerServiceURL());
    }
}
