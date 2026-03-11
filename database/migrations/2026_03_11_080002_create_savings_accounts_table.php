<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('account_number', 15)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('savings_product_id')->constrained('savings_products');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('status', 20)->default('active');
            $table->decimal('balance', 18, 2)->default(0);
            $table->decimal('hold_amount', 18, 2)->default(0);
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('accrued_interest', 18, 2)->default(0);
            $table->date('opened_at');
            $table->date('closed_at')->nullable();
            $table->date('last_interest_posted_at')->nullable();
            $table->date('last_transaction_at')->nullable();
            $table->date('dormant_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
