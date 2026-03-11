<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_parameters', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100);
            $table->text('value');
            $table->string('type', 20)->default('string');
            $table->string('description', 255)->nullable();
            $table->boolean('is_editable')->default(true);
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }
};
