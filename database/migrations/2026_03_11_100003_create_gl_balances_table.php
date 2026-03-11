<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->integer('period_year');
            $table->integer('period_month');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('debit_total', 18, 2)->default(0);
            $table->decimal('credit_total', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['chart_of_account_id', 'branch_id', 'period_year', 'period_month'], 'gl_balances_unique');
        });
    }
};
