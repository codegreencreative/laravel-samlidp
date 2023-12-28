<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Tests;

use CodeGreenCreative\SamlIdp\Jobs\SamlSso;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

class SamlSsoTest extends TestCase
{
    /**
     * @var string
     */
    public string $certificate = '';

    /**
     * @var string
     */
    public string $key = '';

    /**
     * @var string
     */
    public string $fakeACS = 'https://test-example.com';


    protected function setUp(): void
    {
        parent::setUp();

        $this->createFakeCertificate();

        config([
            'samlidp.email_field' => 'email',
            'samlidp.cert' => $this->certificate,
            'samlidp.key' => $this->key,
        ]);

        // This job is dispatched in the middle of a request-response cycle - so we need to create the request that it's
        // expecting with an authenticated user
        $user = new User();
        $user->email = 'fake_email'; // This email HAS to be set for the saml assertion to be created
        $this->actingAs($user);

        $request = Request::create('route-doesnt-matter-here', 'POST', ['SAMLRequest'=> $this->createFakeSamlRequest()]);
        $this->swap('request', $request);
    }

    private function createFakeCertificate(): void
    {
        // create new private and public key
        $res = openssl_pkey_new();

        // generate a certificate signing request
        $csr = openssl_csr_new([uniqid()], $res);

        // sign CSR
        $signing = openssl_csr_sign($csr, null, $res, 365, ['digest_alg'=>'sha256']);

        // export the certificate and private key to string
        openssl_x509_export($signing, $this->certificate);
        openssl_pkey_export($res, $this->key);
    }

    private function createFakeSamlRequest(): string
    {
        $fakeSamlRequestXml = '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
ID="ONELOGIN_809707f0030a5d00620c9d9df97f627afe9dcc24"
Version="2.0"
ProviderName="SP test"
IssueInstant="2014-07-16T23:52:45Z"
Destination="http://idp.example.com/SSOService.php"
ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
AssertionConsumerServiceURL="'.$this->fakeACS.'">
<saml:Issuer>http://sp.example.com/demo1/metadata.php</saml:Issuer>
<samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"
AllowCreate="true" />
<samlp:RequestedAuthnContext Comparison="exact">
<saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
</samlp:RequestedAuthnContext>
</samlp:AuthnRequest>';

        // gzip inflate
        $gzSamlRequest = gzdeflate($fakeSamlRequestXml);

        // base64 encode
        return base64_encode($gzSamlRequest);
    }


    // The following test case is to ensure that when the 'service_provider_model_usage' config variable is not set,
    // the SamlSso job will run as normal and use the configuration data provided in the config to create a response.
    #[Test]
    public function use_config_variables_to_create_response_when_database_access_config_variable_is_not_set(): void
    {
        // Arrange
        $fakeSPConfig = [
             'destination' => 'https://faketest.com',
             'logout' => 'https://anotherfaketest.com',
             'certificate' => '',
             'query_params' => false,
             'encrypt_assertion' => false
        ];

        $encodedAcsUrl = base64_encode($this->fakeACS);
        config([
            'samlidp.sp' => [
                $encodedAcsUrl => $fakeSPConfig
            ]
        ]);

        // Act
        $samlResponse = (new SamlSso())->handle();

        // Assert
        $this->assertNotNull($samlResponse);

        $expectedFormTag = '<form method="post" action="' . $fakeSPConfig['destination'] .'">';
        $this->assertTrue(str_contains($samlResponse, $expectedFormTag));
    }
}
