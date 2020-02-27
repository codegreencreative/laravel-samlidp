<?php

namespace CodeGreenCreative\SamlIdp\Jobs;

use CodeGreenCreative\SamlIdp\Contracts\SamlContract;
use CodeGreenCreative\SamlIdp\Events\Assertion as AssertionEvent;
use CodeGreenCreative\SamlIdp\Traits\PerformsSingleSignOn;
use Illuminate\Foundation\Bus\Dispatchable;
use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\AudienceRestriction;
use LightSaml\Model\Assertion\AuthnContext;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Assertion\EncryptedAssertionWriter;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SamlSso implements SamlContract
{
    use Dispatchable, PerformsSingleSignOn;

    /**
     * [__construct description]
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $deserializationContext = new DeserializationContext;
        $deserializationContext->getDocument()->loadXML(gzinflate(base64_decode(request('SAMLRequest'))));

        $this->authn_request = new AuthnRequest;
        $this->authn_request->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);

        $this->setDestination();

        return $this->response();
    }

    public function response()
    {
        $this->response = (new Response)->setIssuer(new Issuer($this->issuer))
            ->setStatus(new Status(new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success')))
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setDestination($this->destination)
            ->setInResponseTo($this->authn_request->getId());

        $assertion = new Assertion;
        $assertion
            ->setId(Helper::generateID())
            ->setIssueInstant(new \DateTime)
            ->setIssuer(new Issuer($this->issuer))
            ->setSignature(new SignatureWriter($this->certificate, $this->private_key))
            ->setSubject(
                (new Subject)
                    ->setNameID((new NameID(auth()->user()->email, SamlConstants::NAME_ID_FORMAT_EMAIL)))
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation)
                            ->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER)
                            ->setSubjectConfirmationData(
                                (new SubjectConfirmationData())
                                    ->setInResponseTo($this->authn_request->getId())
                                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                                    ->setRecipient($this->authn_request->getAssertionConsumerServiceURL())
                            )
                    )
            )
            ->setConditions(
                (new Conditions)
                    ->setNotBefore(new \DateTime)
                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                    ->addItem(
                        new AudienceRestriction([$this->authn_request->getIssuer()->getValue()])
                    )
            )
            ->addItem(
                (new AuthnStatement)
                    ->setAuthnInstant(new \DateTime('-10 MINUTE'))
                    ->setSessionIndex(Helper::generateID())
                    ->setAuthnContext(
                        (new AuthnContext)
                            ->setAuthnContextClassRef(SamlConstants::NAME_ID_FORMAT_UNSPECIFIED)
                    )
            );

        $attribute_statement = new AttributeStatement;
        event(new AssertionEvent($attribute_statement));
        // Add the attributes to the assertion
        $assertion->addItem($attribute_statement);

        // Encrypt the assertion
        if (config('samlidp.encrypt_assertion')) {
            $this->setSpCertificate();

            $encryptedAssertion = new EncryptedAssertionWriter();
            $encryptedAssertion->encrypt($assertion, KeyHelper::createPublicKey(
                (new X509Certificate)->loadPem($this->sp_certificate)
            ));
            $this->response->addEncryptedAssertion($encryptedAssertion);
        } else {
            $this->response->addAssertion($assertion);
        }

        if (config('samlidp.messages_signed')) {
            $this->response->setSignature(new SignatureWriter($this->certificate, $this->private_key));
        }

        return $this->send(SamlConstants::BINDING_SAML2_HTTP_POST);
    }

    /**
     * [sendSamlRequest description]
     *
     * @param  Request $request [description]
     * @param  User    $user    [description]
     * @return [type]           [description]
     */
    public function send($binding_type)
    {
        $bindingFactory = new BindingFactory;
        $postBinding = $bindingFactory->create($binding_type);
        $messageContext = new MessageContext;
        $messageContext->setMessage($this->response)->asResponse();
        $message = $messageContext->getMessage();
        $message->setRelayState(request('RelayState'));
        $httpResponse = $postBinding->send($messageContext);

        return $httpResponse->getContent();
    }

    private function setDestination()
    {
        $destination = config(sprintf(
            'samlidp.sp.%s.destination',
            $this->getServiceProvider($this->authn_request)
        ));

        $queryParams = $this->getQueryParams();
        if (!empty($queryParams)) {
            $destination = Str::finish(url($destination), '?') . Arr::query($queryParams);
        }

        $this->destination = $destination;
    }

    private function getQueryParams()
    {
        $queryParams = config(sprintf(
            'samlidp.sp.%s.query_params',
            $this->getServiceProvider($this->authn_request)
        ));

        if (is_null($queryParams)) {
            $queryParams = [
                'idp' => config('app.url')
            ];
        }

        return $queryParams;
    }

    public function setSpCertificate()
    {
        $this->sp_certificate = config(sprintf(
            'samlidp.sp.%s.certificate',
            $this->getServiceProvider($this->authn_request)
        ));
    }
}
