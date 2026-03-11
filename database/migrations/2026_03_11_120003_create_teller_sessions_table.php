<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teller_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('vault_id')->constrained('vaults');
            $table->string('status', 20)->default('open');
            $table->decimal('opening_balance', 18, 2);
            $table->decimal('current_balance', 18, 2);
            $table->decimal('closing_balance', 18, 2)->nullable();
            $table->decimal('total_cash_in', 18, 2)->default(0);
            $table->decimal('total_cash_out', 18, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }
};
