<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('account_number', 15)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('loan_product_id')->constrained('loan_products');
            $table->foreignId('loan_application_id')->nullable()->constrained('loan_applications');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('status', 20)->default('active');
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_rate', 8, 5);
            $table->integer('tenor_months');
            $table->decimal('outstanding_principal', 18, 2);
            $table->decimal('outstanding_interest', 18, 2)->default(0);
            $table->decimal('accrued_interest', 18, 2)->default(0);
            $table->decimal('total_principal_paid', 18, 2)->default(0);
            $table->decimal('total_interest_paid', 18, 2)->default(0);
            $table->decimal('total_penalty_paid', 18, 2)->default(0);
            $table->date('disbursement_date');
            $table->date('maturity_date');
            $table->date('last_payment_date')->nullable();
            $table->integer('dpd')->default(0);
            $table->integer('collectibility')->default(1);
            $table->decimal('ckpn_amount', 18, 2)->default(0);
            $table->foreignId('loan_officer_id')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps(precision: 6);
            $table->softDeletes(precision: 6);

            $table->index('status');
            $table->index('collectibility');
            $table->index('maturity_date');
        });
    }
};
