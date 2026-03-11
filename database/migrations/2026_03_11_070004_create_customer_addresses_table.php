<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type', 20)->default('domicile');
            $table->text('address');
            $table->string('rt_rw', 10)->nullable();
            $table->string('kelurahan', 50)->nullable();
            $table->string('kecamatan', 50)->nullable();
            $table->string('city', 50);
            $table->string('province', 50);
            $table->string('postal_code', 5)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps(precision: 6);
        });
    }
};
