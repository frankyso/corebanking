<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eod_processes', function (Blueprint $table) {
            $table->id();
            $table->date('process_date')->unique();
            $table->string('status', 20)->default('pending');
            $table->integer('total_steps')->default(0);
            $table->integer('completed_steps')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('status');
        });
    }
};
