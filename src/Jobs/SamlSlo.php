<?php

namespace CodeGreenCreative\SamlIdp\Jobs;

use CodeGreenCreative\SamlIdp\Traits\PerformsSingleSignOn;
use Illuminate\Foundation\Bus\Dispatchable;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SamlSlo
{
    use Dispatchable, PerformsSingleSignOn;

    private $sp;

    /**
     * [__construct description]
     * @param [type] $sp [description]
     */
    public function __construct($sp)
    {
        $this->sp = $sp;
        $this->init();
    }

    /**
     * [handle description]
     * @param  [type] $sp [description]
     * @return [type]     [description]
     */
    public function handle()
    {
        $this->setDestination();
        // We are receiving a Logout Request
        if (request()->filled('SAMLRequest')) {
            $xml = gzinflate(base64_decode(request('SAMLRequest')));
            $deserializationContext = new DeserializationContext;
            $deserializationContext->getDocument()->loadXML($xml);
            // Get the final destination
            session()->put('RelayState', request('RelayState'));
        } elseif (request()->filled('SAMLResponse')) {
            $xml = gzinflate(base64_decode(request('SAMLResponse')));
            $deserializationContext = new DeserializationContext;
            $deserializationContext->getDocument()->loadXML($xml);
        }
        // Send the request to log out
        return $this->request();
    }

    /**
     * [response description]
     * @return [type] [description]
     */
    public function response()
    {
        $this->response = (new LogoutResponse)->setIssuer(new Issuer($this->issuer))
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setDestination($this->destination)
            ->setInResponseTo($this->logout_request->getId())
            ->setStatus(new Status(new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success')));

        if (config('samlidp.messages_signed')) {
            $this->response->setSignature(new SignatureWriter($this->certificate, $this->private_key, $this->digest_algorithm));
        }

        return $this->send(SamlConstants::BINDING_SAML2_HTTP_REDIRECT);
    }

    /**
     * [request description]
     * @return [type] [description]
     */
    public function request()
    {
        $this->response = (new LogoutRequest)
            ->setIssuer(new Issuer($this->issuer))
            ->setNameID((new NameID(Helper::generateID(), SamlConstants::NAME_ID_FORMAT_TRANSIENT)))
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setDestination($this->destination);

        if (config('samlidp.messages_signed')) {
            $this->response->setSignature(new SignatureWriter($this->certificate, $this->private_key, $this->digest_algorithm));
        }

        return $this->send(SamlConstants::BINDING_SAML2_HTTP_REDIRECT);
    }

    private function setDestination()
    {
        $destination = $this->sp['logout'];
        $queryParams = $this->getQueryParams();
        if (!empty($queryParams)) {
            if (!parse_url($destination, PHP_URL_QUERY)){
                $destination = Str::finish(url($destination), '?') . Arr::query($queryParams);
            }
            else{
                $destination .= '&'.Arr::query($queryParams);
            }
        }

        $this->destination = $destination;
    }

   private function getQueryParams()
   {
        $queryParams = (isset($this->sp['query_params']) ? $this->sp['query_params'] : null);

        if (is_null($queryParams)) {
            $queryParams = [
                'idp' => config('app.url')
            ];
        }

        return $queryParams;
   }
}
