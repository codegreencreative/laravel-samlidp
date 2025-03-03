<?php

namespace CodeGreenCreative\SamlIdp\Jobs;

use LightSaml\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LightSaml\SamlConstants;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Protocol\Status;
use LightSaml\Binding\BindingFactory;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Assertion\Conditions;
use LightSaml\Model\Protocol\AuthnRequest;
use Illuminate\Foundation\Bus\Dispatchable;
use LightSaml\Model\Assertion\AuthnContext;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\AudienceRestriction;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Context\DeserializationContext;
use CodeGreenCreative\SamlIdp\Contracts\SamlContract;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Assertion\EncryptedAssertionWriter;
use CodeGreenCreative\SamlIdp\Traits\PerformsSingleSignOn;
use CodeGreenCreative\SamlIdp\Events\Assertion as AssertionEvent;
use CodeGreenCreative\SamlIdp\Exceptions\DestinationMissingException;

class SamlSso implements SamlContract
{
    use Dispatchable;
    use PerformsSingleSignOn;

    private $authn_request;

    private $response;

    private $guard;

    private $destination;

    /**
     * [__construct description]
     */
    public function __construct($guard = null)
    {
        $this->guard = $guard;
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
        $this->response = (new Response)
            ->setIssuer(new Issuer($this->issuer))
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
            ->setSignature(new SignatureWriter($this->certificate, $this->private_key, $this->digest_algorithm))
            ->setSubject(
                (new Subject)
                    ->setNameID(
                        new NameID(
                            auth($this->guard)
                                ->user()
                                ->__get(config('samlidp.email_field', 'email')),
                            config('samlidp.email_name_id', SamlConstants::NAME_ID_FORMAT_EMAIL)
                        )
                    )
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation)
                            ->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER)
                            ->setSubjectConfirmationData(
                                (new SubjectConfirmationData)
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
                    ->addItem(new AudienceRestriction([$this->authn_request->getIssuer()->getValue()]))
            )
            ->addItem(
                (new AuthnStatement)
                    ->setAuthnInstant(new \DateTime('-10 MINUTE'))
                    ->setSessionIndex(Helper::generateID())
                    ->setAuthnContext(
                        (new AuthnContext)->setAuthnContextClassRef(SamlConstants::NAME_ID_FORMAT_UNSPECIFIED)
                    )
            );

        $attribute_statement = new AttributeStatement;
        event(new AssertionEvent($attribute_statement, $this->guard));
        // Add the attributes to the assertion
        $assertion->addItem($attribute_statement);

        // Encrypt the assertion

        if ($this->encryptAssertion()) {
            $encryptedAssertion = new EncryptedAssertionWriter;
            $encryptedAssertion->encrypt($assertion, KeyHelper::createPublicKey($this->getSpCertificate()));
            $this->response->addEncryptedAssertion($encryptedAssertion);
        } else {
            $this->response->addAssertion($assertion);
        }

        if (config('samlidp.messages_signed')) {
            $this->response->setSignature(
                new SignatureWriter($this->certificate, $this->private_key, $this->digest_algorithm)
            );
        }

        return $this->send(SamlConstants::BINDING_SAML2_HTTP_POST);
    }

    /**
     * [sendSamlRequest description]
     *
     * @param  Request  $request  [description]
     * @param  User  $user  [description]
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
        $destination = $this->getServiceProviderConfigValue($this->authn_request, 'destination');

        if (empty($destination)) {
            throw new DestinationMissingException(
                sprintf(
                    '%s does not have a destination set in config file.',
                    $this->getServiceProvider($this->authn_request)
                )
            );
        }

        $queryParams = $this->getQueryParams();
        if (is_array($queryParams) && !empty($queryParams)) {
            if (!parse_url($destination, PHP_URL_QUERY)) {
                $destination = Str::finish(url($destination), '?') . Arr::query($queryParams);
            } else {
                $destination .= '&' . Arr::query($queryParams);
            }
        }

        $this->destination = $destination;
    }

    private function getQueryParams()
    {
        $queryParams = $this->getServiceProviderConfigValue($this->authn_request, 'query_params');

        if (is_null($queryParams)) {
            $queryParams = [
                'idp' => config('app.url'),
            ];
        }

        return $queryParams;
    }

    private function getSpCertificate()
    {
        $spCertificate = $this->getServiceProviderConfigValue($this->authn_request, 'certificate');

        return strpos($spCertificate, 'file://') === 0
            ? X509Certificate::fromFile($spCertificate)
            : (new X509Certificate)->loadPem($spCertificate);
    }

    /**
     * Check to see if the SP wants to encrypt assertions first
     * If its not set, default to base encryption assertion config
     * Otherwise return true
     */
    private function encryptAssertion(): bool
    {
        return $this->getServiceProviderConfigValue($this->authn_request, 'encrypt_assertion')
            ?? config('samlidp.encrypt_assertion', true);
    }
}
