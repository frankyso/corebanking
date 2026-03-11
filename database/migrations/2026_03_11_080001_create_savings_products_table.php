<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_products', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('interest_calc_method', 20);
            $table->decimal('interest_rate', 8, 5)->default(0);
            $table->decimal('min_opening_balance', 18, 2)->default(0);
            $table->decimal('min_balance', 18, 2)->default(0);
            $table->decimal('max_balance', 18, 2)->nullable();
            $table->decimal('admin_fee_monthly', 18, 2)->default(0);
            $table->decimal('closing_fee', 18, 2)->default(0);
            $table->decimal('dormant_fee', 18, 2)->default(0);
            $table->integer('dormant_period_days')->default(365);
            $table->decimal('tax_rate', 8, 5)->default(20);
            $table->decimal('tax_threshold', 18, 2)->default(7_500_000);
            $table->boolean('is_active')->default(true);
            $table->foreignId('gl_savings_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_interest_expense_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_interest_payable_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_admin_fee_income_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('gl_tax_payable_id')->nullable()->constrained('chart_of_accounts');
            $table->timestamps(precision: 6);
            $table->softDeletes(precision: 6);
        });
    }
};
