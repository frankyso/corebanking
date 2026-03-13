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
        Schema::create('transfer_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('savings_account_id')->constrained();
            $table->string('alias');
            $table->timestamps(precision: 6);

            $table->unique(['mobile_user_id', 'savings_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_favorites');
    }
};
