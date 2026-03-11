<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('account_number', 15)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('deposit_product_id')->constrained('deposit_products');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('status', 20)->default('active');
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_rate', 8, 5);
            $table->integer('tenor_months');
            $table->string('interest_payment_method', 20);
            $table->string('rollover_type', 30)->default('none');
            $table->date('placement_date');
            $table->date('maturity_date');
            $table->date('last_interest_paid_at')->nullable();
            $table->decimal('accrued_interest', 18, 2)->default(0);
            $table->decimal('total_interest_paid', 18, 2)->default(0);
            $table->decimal('total_tax_paid', 18, 2)->default(0);
            $table->boolean('is_pledged')->default(false);
            $table->string('pledge_reference')->nullable();
            $table->foreignId('savings_account_id')->nullable()->constrained('savings_accounts');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['maturity_date', 'status']);
        });
    }
};
