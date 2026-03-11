<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_collaterals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_application_id')->nullable()->constrained('loan_applications');
            $table->foreignId('loan_account_id')->nullable()->constrained('loan_accounts');
            $table->string('collateral_type', 20);
            $table->string('description');
            $table->string('document_number')->nullable();
            $table->decimal('appraised_value', 18, 2);
            $table->decimal('liquidation_value', 18, 2)->nullable();
            $table->string('location')->nullable();
            $table->string('ownership_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps(precision: 6);
        });
    }
};
