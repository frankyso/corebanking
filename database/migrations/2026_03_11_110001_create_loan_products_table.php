<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('loan_type', 20);
            $table->string('interest_type', 20);
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->decimal('interest_rate', 8, 5);
            $table->integer('min_tenor_months')->default(1);
            $table->integer('max_tenor_months');
            $table->decimal('admin_fee_rate', 8, 5)->default(0);
            $table->decimal('provision_fee_rate', 8, 5)->default(0);
            $table->decimal('insurance_rate', 8, 5)->default(0);
            $table->decimal('penalty_rate', 8, 5)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('gl_loan_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_interest_income_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_interest_receivable_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_fee_income_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_provision_id')->nullable()->constrained('chart_of_accounts');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
