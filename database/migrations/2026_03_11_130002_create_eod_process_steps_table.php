<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eod_process_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eod_process_id')->constrained('eod_processes')->cascadeOnDelete();
            $table->integer('step_number');
            $table->string('step_name', 100);
            $table->string('status', 20)->default('pending');
            $table->integer('records_processed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['eod_process_id', 'step_number']);
        });
    }
};
