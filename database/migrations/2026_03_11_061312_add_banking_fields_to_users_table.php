<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('employee_id', 20)->unique()->nullable()->after('id');
            $table->foreignId('branch_id')->nullable()->after('employee_id')->constrained('branches')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('password');
        });
    }
};
