<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Tests;

use CodeGreenCreative\SamlIdp\Jobs\SamlSlo;
use CodeGreenCreative\SamlIdp\Models\ServiceProvider;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var User
     */
    public User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }

    /** @test */
    public function dispatch_slo_for_service_provider_in_samlidp_config(): void
    {
        // Arrange
        $destination = 'https://faketest.com';
        $encodedAcsUrl = base64_encode($destination);
        $fakeSPConfig = [
            $encodedAcsUrl => [
             'destination' => $destination,
             'logout' => 'https://anotherfaketest.com',
             'certificate' => '',
             'query_params' => false,
             'encrypt_assertion' => false
            ]
        ];

        config(['samlidp.sp' => $fakeSPConfig]);

        $sloMock = \Mockery::mock('overload:' . SamlSlo::class)->makePartial();

        // Act
        $this->actingAs($this->user)->get('/saml/logout');

        // Assert
        $sloMock->shouldHaveReceived('dispatchSync')->with($fakeSPConfig[$encodedAcsUrl]);
    }

    /** @test */
    public function do_not_access_database_if_service_provider_model_usage_variable_is_not_configured(): void
    {
        // Arrange
        $destination = 'https://faketest.com';
        $encodedAcsUrl = base64_encode($destination);
        $fakeSPConfig = [
            $encodedAcsUrl => [
                'destination' => $destination,
                'logout' => 'https://anotherfaketest.com',
                'certificate' => '',
                'query_params' => false,
                'encrypt_assertion' => false
            ]
        ];

        config(['samlidp.sp' => $fakeSPConfig]);

        // We do not want to actually run the slo job
        $sloMock = \Mockery::mock('overload:' . SamlSlo::class);
        DB::connection()->enableQueryLog();

        // Act
        $this->actingAs($this->user)->get('/saml/logout');

        // Assert
        $this->assertEmpty(DB::getQueryLog());
    }

    /** @test */
    public function do_not_access_database_if_service_provider_model_usage_variable_is_false(): void
    {
        // Arrange
        config([
            'samlidp.service_provider_model_usage' => false,
        ]);

        $destination = 'https://faketest.com';
        $encodedAcsUrl = base64_encode($destination);
        $fakeSPConfig = [
            $encodedAcsUrl => [
                'destination' => $destination,
                'logout' => 'https://anotherfaketest.com',
                'certificate' => '',
                'query_params' => false,
                'encrypt_assertion' => false
            ]
        ];

        config(['samlidp.sp' => $fakeSPConfig]);

        // We do not want to actually run the slo job
        $sloMock = \Mockery::mock('overload:' . SamlSlo::class);
        DB::connection()->enableQueryLog();

        // Act
        $this->actingAs($this->user)->get('/saml/logout');

        // Assert
        $this->assertEmpty(DB::getQueryLog());
    }

    // The logout controller relies on service provider configurations to be saved to samlidp config. So, if we have
    // service provider configurations saved to the db - we need to make sure that the configurations are added to the
    // config file before the slo job is called.
    /** @test */
    public function add_sp_model_configurations_saved_in_the_database_to_samlidp_config(): void
    {
        // Arrange
        config([
            'samlidp.service_provider_model_usage' => true,
            'samlidp.service_provider_model' => ServiceProvider::class,
        ]);

        $serviceProvider1 = factory(ServiceProvider::class)->create();
        $serviceProvider2 = factory(ServiceProvider::class)->create();

        // Act
        $this->actingAs($this->user)->get('/saml/logout');

        // Assert
        $spConfig1 = [
            'destination' => $serviceProvider1->destination_url,
            'logout' => $serviceProvider1->logout_url,
            'certificate' => $serviceProvider1->certificate,
            'query_params' => $serviceProvider1->query_params,
            'encrypt_assertion' => $serviceProvider1->encrypt_assertion,
            'block_encryption_algorithm' => $serviceProvider1->block_encryption_algorithm,
            'key_transport_encryption' => $serviceProvider1->key_transport_encryption,
        ];

        $spConfig2 = [
            'destination' => $serviceProvider2->destination_url,
            'logout' => $serviceProvider2->logout_url,
            'certificate' => $serviceProvider2->certificate,
            'query_params' => $serviceProvider2->query_params,
            'encrypt_assertion' => $serviceProvider2->encrypt_assertion,
            'block_encryption_algorithm' => $serviceProvider2->block_encryption_algorithm,
            'key_transport_encryption' => $serviceProvider2->key_transport_encryption,
        ];

        $serviceProvidersInConfig = config('samlidp.sp');
        $spEncoded = base64_encode($serviceProvider1->destination_url);
        $this->assertArrayHasKey($spEncoded, $serviceProvidersInConfig);
        $this->assertEquals($spConfig1, $serviceProvidersInConfig[$spEncoded]);


        $spEncoded = base64_encode($serviceProvider2->destination_url);
        $this->assertArrayHasKey($spEncoded, $serviceProvidersInConfig);
        $this->assertEquals($spConfig2, $serviceProvidersInConfig[$spEncoded]);
    }
}
