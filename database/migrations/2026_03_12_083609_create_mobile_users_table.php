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
        Schema::create('mobile_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone_number')->unique();
            $table->string('pin_hash');
            $table->unsignedTinyInteger('pin_attempts')->default(0);
            $table->timestamp('pin_locked_until', precision: 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at', precision: 6)->nullable();
            $table->timestamps(precision: 6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_users');
    }
};
