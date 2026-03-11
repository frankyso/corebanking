<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 30)->unique();
            $table->foreignId('loan_account_id')->constrained('loan_accounts');
            $table->string('payment_type', 30);
            $table->decimal('amount', 18, 2);
            $table->decimal('principal_portion', 18, 2)->default(0);
            $table->decimal('interest_portion', 18, 2)->default(0);
            $table->decimal('penalty_portion', 18, 2)->default(0);
            $table->date('payment_date');
            $table->string('description')->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->timestamps(precision: 6);

            $table->index('payment_date');
        });
    }
};
