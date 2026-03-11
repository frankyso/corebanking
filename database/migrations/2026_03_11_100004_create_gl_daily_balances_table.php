<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_daily_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->date('balance_date');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('debit_total', 18, 2)->default(0);
            $table->decimal('credit_total', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['chart_of_account_id', 'branch_id', 'balance_date'], 'gl_daily_balances_unique');
            $table->index('balance_date');
        });
    }
};
