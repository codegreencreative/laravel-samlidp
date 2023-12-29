<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Database\Factories;

use CodeGreenCreative\SamlIdp\Models\ServiceProvider;
use RobRichards\XMLSecLibs\XMLSecurityKey;


$factory->define(ServiceProvider::class, function () {
    return [
        'destination_url' => $this->faker->url,
        'logout_url' => $this->faker->url,
        'certificate' => $this->faker->text(100),
        'block_encryption_algorithm' => XMLSecurityKey::AES128_CBC,
        'key_transport_encryption' => XMLSecurityKey::RSA_1_5,
        'query_parameters' => false,
        'encrypt_assertion' => false,
    ];
});
