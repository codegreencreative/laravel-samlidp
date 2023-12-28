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
use RobRichards\XMLSecLibs\XMLSecurityKey;
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
        $deserializationContext = new DeserializationContext();
        $deserializationContext->getDocument()->loadXML(gzinflate(base64_decode(request('SAMLRequest'))));

        $this->authn_request = new AuthnRequest();
        $this->authn_request->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);

        $this->setDestination();

        return $this->response();
    }

    public function response()
    {
        $this->response = (new Response())
            ->setIssuer(new Issuer($this->issuer))
            ->setStatus(new Status(new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success')))
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($this->destination)
            ->setInResponseTo($this->authn_request->getId());

        $assertion = new Assertion();
        $assertion
            ->setId(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setIssuer(new Issuer($this->issuer))
            ->setSignature(new SignatureWriter($this->certificate, $this->private_key, $this->digest_algorithm))
            ->setSubject(
                (new Subject())
                    ->setNameID(
                        new NameID(
                            auth($this->guard)
                                ->user()
                                ->__get(config('samlidp.email_field', 'email')),
                            SamlConstants::NAME_ID_FORMAT_EMAIL
                        )
                    )
                    ->addSubjectConfirmation(
                        (new SubjectConfirmation())
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
                (new Conditions())
                    ->setNotBefore(new \DateTime())
                    ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                    ->addItem(new AudienceRestriction([$this->authn_request->getIssuer()->getValue()]))
            )
            ->addItem(
                (new AuthnStatement())
                    ->setAuthnInstant(new \DateTime('-10 MINUTE'))
                    ->setSessionIndex(Helper::generateID())
                    ->setAuthnContext(
                        (new AuthnContext())->setAuthnContextClassRef(SamlConstants::NAME_ID_FORMAT_UNSPECIFIED)
                    )
            );

        $attribute_statement = new AttributeStatement();
        event(new AssertionEvent($attribute_statement, $this->guard));
        // Add the attributes to the assertion
        $assertion->addItem($attribute_statement);

        // Encrypt the assertion

        if ($this->encryptAssertion()) {
            $encryptedAssertion = $this->getEncryptionAssertionWriter();
            $encryptedAssertion->encrypt($assertion, KeyHelper::createPublicKey(
                $this->getSpCertificate()
            ));
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
     * @param Request $request [description]
     * @param User $user [description]
     * @return [type]           [description]
     */
    public function send($binding_type)
    {
        $bindingFactory = new BindingFactory();
        $postBinding = $bindingFactory->create($binding_type);
        $messageContext = new MessageContext();
        $messageContext->setMessage($this->response)->asResponse();
        $message = $messageContext->getMessage();
        $message->setRelayState(request('RelayState'));
        $httpResponse = $postBinding->send($messageContext);

        return $httpResponse->getContent();
    }

    private function setDestination()
    {
        if (config('samlidp.service_provider_model_usage')) {
            $spModelClass = config('samlidp.service_provider_model');
            $spRequesting = $this->authn_request->getAssertionConsumerServiceURL();

            $serviceProvider = $spModelClass::where('destination_url', $spRequesting)->firstOrFail();
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

        $destination = config(sprintf('samlidp.sp.%s.destination', $this->getServiceProvider($this->authn_request)));

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
        $queryParams = config(sprintf('samlidp.sp.%s.query_params', $this->getServiceProvider($this->authn_request)));

        if (is_null($queryParams)) {
            $queryParams = [
                'idp' => config('app.url'),
            ];
        }

        return $queryParams;
    }

    private function getSpCertificate()
    {
        $spCertificate = config(sprintf(
            'samlidp.sp.%s.certificate',
            $this->getServiceProvider($this->authn_request)
        ));

        return (strpos($spCertificate, 'file://') === 0)
            ? X509Certificate::fromFile($spCertificate)
            : (new X509Certificate)->loadPem($spCertificate);
    }

    /**
     * Check to see if the SP wants to encrypt assertions first
     * If its not set, default to base encryption assertion config
     * Otherwise return true
     *
     * @return boolean
     */
    private function encryptAssertion(): bool
    {
        return config(
            sprintf('samlidp.sp.%s.encrypt_assertion', $this->getServiceProvider($this->authn_request)),
            config('samlidp.encrypt_assertion', true)
        );
    }

    private function getEncryptionAssertionWriter() {
        $blockEncryptionAlgorithm = config(sprintf(
            'samlidp.sp.%s.block_encryption_algorithm', $this->getServiceProvider($this->authn_request)
        ));

        $keyTransportEncryption = config(sprintf(
            'samlidp.sp.%s.key_transport_encryption', $this->getServiceProvider($this->authn_request)
        ));

        // because PHP < 7.4 is supported in this package we can't use the null coalescing assignment operator (??=)
        $blockEncryptionAlgorithm = $blockEncryptionAlgorithm ?? XMLSecurityKey::AES128_CBC;
        $keyTransportEncryption = $keyTransportEncryption ?? XMLSecurityKey::RSA_1_5;

        return new EncryptedAssertionWriter($blockEncryptionAlgorithm, $keyTransportEncryption);
    }
}
