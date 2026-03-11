<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 30)->unique();
            $table->foreignId('deposit_account_id')->constrained('deposit_accounts');
            $table->string('transaction_type', 30);
            $table->decimal('amount', 18, 2);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('performed_by')->constrained('users');
            $table->foreignId('journal_entry_id')->nullable();
            $table->timestamps();

            $table->index(['deposit_account_id', 'transaction_date']);
        });
    }
};
