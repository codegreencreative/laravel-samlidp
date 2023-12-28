<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Database\Factories;

use CodeGreenCreative\SamlIdp\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class ServiceProviderFactory extends Factory
{
    protected $model = ServiceProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'destination_url' => $this->faker->url,
            'logout_url' => $this->faker->url,
            'certificate' => $this->faker->text(100),
            'block_encryption_algorithm' => XMLSecurityKey::AES128_CBC,
            'key_transport_encryption' => XMLSecurityKey::RSA_1_5,
            'query_parameters' => false,
            'encrypt_assertion' => false,
        ];
    }
}
