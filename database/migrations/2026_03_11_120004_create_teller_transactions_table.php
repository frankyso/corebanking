<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teller_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 30)->unique();
            $table->foreignId('teller_session_id')->constrained('teller_sessions');
            $table->string('transaction_type', 30);
            $table->decimal('amount', 18, 2);
            $table->decimal('teller_balance_before', 18, 2);
            $table->decimal('teller_balance_after', 18, 2);
            $table->string('direction', 5);
            $table->string('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversed_by_id')->nullable()->constrained('teller_transactions');
            $table->boolean('needs_authorization')->default(false);
            $table->foreignId('authorized_by')->nullable()->constrained('users');
            $table->timestamp('authorized_at')->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->timestamps();

            $table->index('transaction_type');
            $table->index(['reference_type', 'reference_id']);
        });
    }
};
