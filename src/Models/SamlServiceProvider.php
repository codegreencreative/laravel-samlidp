<?php

namespace CodeGreenCreative\SamlIdp\Models;

use Illuminate\Database\Eloquent\Model;

class SamlServiceProvider extends Model
{
    protected $table = 'laravel_samlidp_service_providers';
    public $incrementing = false;
    protected $keyType = 'string';
}
