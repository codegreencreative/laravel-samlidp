<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Models;

use CodeGreenCreative\SamlIdp\Database\Factories\ServiceProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_url',
        'logout_url',
        'certificate',
        'block_encryption_algorithm',
        'key_transport_encryption',
        'query_parameters',
        'encrypt_assertion',
    ];

    protected static function newFactory(): ServiceProviderFactory
    {
        return ServiceProviderFactory::new();
    }
}
