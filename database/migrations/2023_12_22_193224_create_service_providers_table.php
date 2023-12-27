<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->string('destination_url')->unique();
            $table->string('logout_url');
            $table->string('certificate');
            $table->string('block_encryption_algorithm');
            $table->string('key_transport_encryption');
            $table->boolean('query_parameters');
            $table->boolean('encrypt_assertion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
