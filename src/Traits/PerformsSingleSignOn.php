<?php

namespace CodeGreenCreative\SamlIdp\Traits;

use Illuminate\Support\Facades\Storage;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

trait PerformsSingleSignOn
{
    private $issuer;
    private $certificate;
    private $private_key;
    private $request;
    private $response;
    private $digest_algorithm;

    /**
     * [__construct description]
     */
    protected function init()
    {
        $this->issuer = url(config('samlidp.issuer_uri'));
        $this->certificate = $this->getCertificate();
        $this->private_key = $this->getKey();
        $this->digest_algorithm = config('samlidp.digest_algorithm', XMLSecurityDSig::SHA1);
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

    /**
     * @return \LightSaml\Credential\X509Certificate
     */
    protected function getCertificate(): X509Certificate
    {
        $certificate = config('samlidp.cert') ?: Storage::disk('samlidp')->get(config('samlidp.certname', 'cert.pem'));

        return (strpos($certificate, 'file://') === 0)
            ? X509Certificate::fromFile($certificate)
            : (new X509Certificate)->loadPem($certificate);
    }

    /**
     * @return \RobRichards\XMLSecLibs\XMLSecurityKey
     */
    protected function getKey(): XMLSecurityKey
    {
        $key = config('samlidp.key') ?: Storage::disk('samlidp')->get(config('samlidp.keyname', 'key.pem'));

        return KeyHelper::createPrivateKey($key, '', strpos($key, 'file://') === 0, XMLSecurityKey::RSA_SHA256);
    }
}
