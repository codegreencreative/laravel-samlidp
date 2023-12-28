<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Tests;

use CodeGreenCreative\SamlIdp\Jobs\SamlSso;
use CodeGreenCreative\SamlIdp\Models\ServiceProvider;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SamlSsoTest extends TestCase
{
    use RefreshDatabase;

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
        $fakeSamlRequestXml = <<<XML
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
         xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
         ID="ONELOGIN_809707f0030a5d00620c9d9df97f627afe9dcc24"
         Version="2.0"
         ProviderName="SP test"
         IssueInstant="2014-07-16T23:52:45Z"
         Destination="http://idp.example.com/SSOService.php"
         ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
         AssertionConsumerServiceURL="$this->fakeACS">
         <saml:Issuer>http://sp.example.com/demo1/metadata.php</saml:Issuer>
         <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"
         AllowCreate="true" />
         <samlp:RequestedAuthnContext Comparison="exact">
         <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
         </samlp:RequestedAuthnContext>
         </samlp:AuthnRequest>
XML;

        // gzip inflate
        $gzSamlRequest = gzdeflate($fakeSamlRequestXml);

        // base64 encode
        return base64_encode($gzSamlRequest);
    }


    // The following test case is to ensure that when the 'service_provider_model_usage' config variable is not set,
    // the SamlSso job will run as normal and use the configuration data provided in the config to create a response.
    /** @test */
    public function create_saml_response_using_samlidp_config(): void
    {
        // Arrange
        $fakeSPConfig = [
             'destination' => $this->fakeACS,
             'logout' => 'https://anotherfaketest.com',
             'certificate' => '',
             'query_params' => false,
             'encrypt_assertion' => false
        ];

        // We HAVE to keep the exact format given in the config, i.e (encoded ACS URL => SP configuration)
        // Otherwise the SamlSso job will not be able to find the correct service provider configuration
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

    /** @test */
    public function do_not_access_database_if_service_provider_model_usage_variable_is_not_configured(): void
    {
        // Arrange
        $fakeSPConfig = [
            'destination' => $this->fakeACS,
            'logout' => 'https://anotherfaketest.com',
            'certificate' => '',
            'query_params' => false,
            'encrypt_assertion' => false
        ];

        // We HAVE to keep the exact format given in the config, i.e (encoded ACS URL => SP configuration)
        // Otherwise the SamlSso job will not be able to find the correct service provider configuration
        $encodedAcsUrl = base64_encode($this->fakeACS);
        config([
            'samlidp.sp' => [
                $encodedAcsUrl => $fakeSPConfig
            ]
        ]);

        DB::connection()->enableQueryLog();

        // Act
        $samlResponse = (new SamlSso())->handle();

        // Assert
        $this->assertEmpty(DB::getQueryLog());
    }

    /** @test */
    public function create_response_using_service_provider_model_when_database_access_is_configured(): void
    {
        // Arrange
        config([
            'samlidp.service_provider_model_usage' => true,
            'samlidp.service_provider_model' => ServiceProvider::class
        ]);
        $serviceProvider = ServiceProvider::factory()->create([
            'destination_url' => $this->fakeACS // This field HAS to match the ACS URL in the SAML request
        ]);

        // Act
        $samlResponse = (new SamlSso())->handle();

        // Assert
        $this->assertNotNull($samlResponse);

        $expectedFormTag = '<form method="post" action="' .$serviceProvider->destination_url.'">';
        $this->assertTrue(str_contains($samlResponse, $expectedFormTag));
    }

    /** @test */
    public function add_sp_model_configuration_to_existing_service_provider_array_in_config(): void
    {
        // Arrange
        // We need to create an existing service provider configuration in the config so that we can assure that we
        // dont overwrite it when we add the model's sp configuration to the file
        config([
            'samlidp.service_provider_model_usage' => true,
            'samlidp.service_provider_model' => ServiceProvider::class,
            'samlidp.sp' => [
                 'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
                     'destination' => 'https://myfacebookworkplace.facebook.com/work/saml.php',
                     'logout' => 'https://myfacebookworkplace.facebook.com/work/sls.php',
                     'certificate' => '',
                     'query_params' => false,
                     'encrypt_assertion' => false
                    ]
            ]
        ]);

        $serviceProvider = ServiceProvider::factory()->create([
            'destination_url' => $this->fakeACS // This field HAS to match the ACS URL in the SAML request
        ]);

        // Act
        $samlResponse = (new SamlSso())->handle();

        // Assert
        $encodedAcsUrl = base64_encode($serviceProvider->destination_url);
        $expectedConfigEntry = [
            'destination' => $serviceProvider->destination_url,
            'logout' => $serviceProvider->logout_url,
            'certificate' => $serviceProvider->certificate,
            'query_params' => $serviceProvider->query_parameters,
            'encrypt_assertion' => $serviceProvider->encrypt_assertion,
            'block_encryption_algorithm' => $serviceProvider->block_encryption_algorithm,
            'key_transport_encryption' => $serviceProvider->key_transport_encryption
        ];

        $this->assertCount(2, config('samlidp.sp'));
        $this->assertEquals($expectedConfigEntry, config("samlidp.sp.$encodedAcsUrl"));
    }
}
