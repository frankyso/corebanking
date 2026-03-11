<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('individual_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('nik', 16)->unique();
            $table->string('full_name', 150);
            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('marital_status', 10)->nullable();
            $table->string('mother_maiden_name', 100)->nullable();
            $table->string('religion', 20)->nullable();
            $table->string('education', 30)->nullable();
            $table->string('nationality', 3)->default('IDN');
            $table->string('npwp', 20)->nullable();
            $table->text('address_ktp')->nullable();
            $table->text('address_domicile')->nullable();
            $table->string('rt_rw', 10)->nullable();
            $table->string('kelurahan', 50)->nullable();
            $table->string('kecamatan', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('province', 50)->nullable();
            $table->string('postal_code', 5)->nullable();
            $table->string('phone_mobile', 20)->nullable();
            $table->string('phone_home', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('occupation', 50)->nullable();
            $table->string('employer_name', 100)->nullable();
            $table->decimal('monthly_income', 18, 2)->default(0);
            $table->string('source_of_fund', 50)->nullable();
            $table->string('transaction_purpose', 50)->nullable();
            $table->timestamps();
        });
    }
};
