<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaults', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->foreignId('branch_id')->constrained('branches');
            $table->decimal('balance', 18, 2)->default(0);
            $table->decimal('minimum_balance', 18, 2)->default(0);
            $table->decimal('maximum_balance', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('custodian_id')->nullable()->constrained('users');
            $table->timestamps(precision: 6);
        });
    }
};
