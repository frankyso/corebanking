<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 30)->unique();
            $table->foreignId('savings_account_id')->constrained('savings_accounts');
            $table->string('transaction_type', 20);
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->date('value_date');
            $table->foreignId('performed_by')->constrained('users');
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('journal_entry_id')->nullable();
            $table->timestamps();

            $table->index(['savings_account_id', 'transaction_date']);
        });
    }
};
