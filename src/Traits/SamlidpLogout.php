<?php

namespace CodeGreenCreative\SamlIdp;

use App\User;
use LightSaml\SamlConstants;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Protocol\AuthnRequest;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Context\Profile\MessageContext;
use Symfony\Component\HttpFoundation\Request;
use LightSaml\Model\Context\DeserializationContext;

class SamlidpLogout
{
    protected $logout_request;

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

        $this->logout_request = new LogoutRequest;
        $this->logout_request->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);
dd($this->logout_request);
        // return auth()->check() ? redirect('/') : view('auth.logout');
    }

    /**
     * [getRequester description]
     *
     * @return [type] [description]
     */
    public function getRequester()
    {
        return $this->logout_request->getNameID()->getValue();
    }
}
