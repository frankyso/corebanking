<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('prefix', 20);
            $table->unsignedInteger('last_number')->default(0);
            $table->unsignedTinyInteger('padding')->default(7);
            $table->timestamps();

            $table->unique(['type', 'prefix']);
        });
    }
};
