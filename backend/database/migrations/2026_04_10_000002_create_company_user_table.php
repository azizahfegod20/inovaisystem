<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('operador');
            $table->timestamps();

            $table->unique(['user_id', 'company_id'], 'idx_company_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
