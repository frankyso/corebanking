<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('cif_number', 20)->unique();
            $table->string('customer_type', 20);
            $table->string('status', 20)->default('pending_approval');
            $table->string('risk_rating', 10)->default('low');
            $table->foreignId('branch_id')->constrained('branches');

            $table->string('approval_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_type');
            $table->index('status');
            $table->index('risk_rating');
        });
    }
};
