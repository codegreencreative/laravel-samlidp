<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laravel_samlidp_service_providers', function (Blueprint $table) {
            $table->string('id')->unique()->comment('acs_url base64 encoded');
            $table->text('acs_url');
            $table->text('destination');
            $table->text('logout');
            $table->longText('certificate');
            $table->boolean('query_params');
            $table->boolean('encrypt_assertion');
            $table->timestamps();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saml_service_providers');
    }
};
