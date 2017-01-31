<?php

namespace Codegreencreative\Idp;

use App\User;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Protocol\AuthnRequest;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\HttpFoundation\Request;
use LightSaml\Model\Context\DeserializationContext;

class SamlidpLogout
{
    /**
     * A request to logout
     *
     * @param  Request $request [description]
     * @param  User    $user    [description]
     * @return [type]           [description]
     */
    public function __construct(Request $request, User $user)
    {
        $xml = gzinflate(base64_decode($request->get('SAMLRequest')));

        $deserializationContext = new DeserializationContext;
        $deserializationContext->getDocument()->loadXML($xml);

        $logout_request = new LogoutRequest;
        $logout_request->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);

        $this->service_provider = $this->getServiceProvider($logout_request);

        // Logging
        $this->samlLog('Service Provider: ' . $logout_request->getAssertionConsumerServiceURL());
        $this->samlLog('Service Provider (base64): ' . $this->service_provider);

        $this->destination = config(sprintf('samlidp.sp.%s.logout', $this->service_provider));
        $this->issuer = url(config('samlidp.issuer_uri'));
        $this->certificate = X509Certificate::fromFile(config('samlidp.crt'));
        $this->private_key = KeyHelper::createPrivateKey(config('samlidp.key'), '', true, XMLSecurityKey::RSA_SHA256);

        return $this->samlResponse($logout_request, $user, $request);
    }

}