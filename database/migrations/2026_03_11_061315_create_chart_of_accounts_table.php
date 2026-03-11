<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 12)->unique();
            $table->string('account_name', 150);
            $table->string('account_group', 20);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_header')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('normal_balance', 10);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_group');
            $table->index('level');
        });
    }
};
