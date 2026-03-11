<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('loan_product_id')->constrained('loan_products');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('status', 20)->default('draft');
            $table->decimal('requested_amount', 18, 2);
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->integer('requested_tenor_months');
            $table->integer('approved_tenor_months')->nullable();
            $table->decimal('interest_rate', 8, 5);
            $table->string('purpose', 255);
            $table->text('notes')->nullable();
            $table->foreignId('loan_officer_id')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }
};
