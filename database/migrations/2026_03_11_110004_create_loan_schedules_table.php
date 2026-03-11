<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_amount', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->decimal('outstanding_balance', 18, 2);
            $table->decimal('principal_paid', 18, 2)->default(0);
            $table->decimal('interest_paid', 18, 2)->default(0);
            $table->decimal('penalty_paid', 18, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->timestamps();

            $table->unique(['loan_account_id', 'installment_number']);
            $table->index('due_date');
        });
    }
};
