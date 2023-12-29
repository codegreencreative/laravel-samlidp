<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    protected $fillable = [
        'destination_url',
        'logout_url',
        'certificate',
        'block_encryption_algorithm',
        'key_transport_encryption',
        'query_parameters',
        'encrypt_assertion',
    ];
}
