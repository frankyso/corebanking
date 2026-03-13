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
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_id', 64)->unique();
            $table->text('secret_key');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('rate_limit')->default(60);
            $table->json('allowed_ips')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamp('last_used_at', 6)->nullable();
            $table->timestamps(precision: 6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
