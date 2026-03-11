<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_product_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deposit_product_id')->constrained('deposit_products')->cascadeOnDelete();
            $table->integer('tenor_months');
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->decimal('interest_rate', 8, 5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['deposit_product_id', 'tenor_months', 'min_amount'], 'deposit_rate_unique');
        });
    }
};
