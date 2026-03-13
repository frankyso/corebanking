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
        Schema::create('transfer_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('source_savings_account_id')->constrained('savings_accounts');
            $table->foreignId('destination_savings_account_id')->constrained('savings_accounts');
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('transfer_type');
            $table->string('status');
            $table->foreignId('performed_by')->nullable()->constrained('mobile_users');
            $table->timestamp('performed_at', precision: 6);
            $table->foreignId('journal_entry_id')->nullable()->constrained();
            $table->timestamps(precision: 6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_transactions');
    }
};
