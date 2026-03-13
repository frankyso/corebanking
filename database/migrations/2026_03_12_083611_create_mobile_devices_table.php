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
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id');
            $table->string('device_name');
            $table->string('platform');
            $table->text('fcm_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at', precision: 6)->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['mobile_user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
