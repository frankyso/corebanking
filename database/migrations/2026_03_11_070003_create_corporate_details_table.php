<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('company_name', 200);
            $table->string('legal_type', 30)->nullable();
            $table->string('nib', 30)->nullable();
            $table->string('npwp_company', 20)->nullable();
            $table->string('deed_number', 50)->nullable();
            $table->date('deed_date')->nullable();
            $table->string('sk_kemenkumham', 50)->nullable();
            $table->string('business_sector', 50)->nullable();
            $table->text('address_company')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('province', 50)->nullable();
            $table->string('postal_code', 5)->nullable();
            $table->string('phone_office', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->decimal('annual_revenue', 18, 2)->default(0);
            $table->unsignedInteger('total_employees')->nullable();
            $table->json('beneficial_owner')->nullable();
            $table->json('authorized_persons')->nullable();
            $table->string('contact_person_name', 100)->nullable();
            $table->string('contact_person_phone', 20)->nullable();
            $table->string('contact_person_position', 50)->nullable();
            $table->timestamps();
        });
    }
};
