<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_interest_accruals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('savings_account_id')->constrained('savings_accounts');
            $table->date('accrual_date');
            $table->decimal('balance', 18, 2);
            $table->decimal('interest_rate', 8, 5);
            $table->decimal('accrued_amount', 18, 2);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->date('posted_at')->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['savings_account_id', 'accrual_date']);
            $table->index(['accrual_date', 'is_posted']);
        });
    }
};
