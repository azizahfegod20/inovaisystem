<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->text('pfx_content');
            $table->text('pfx_password');
            $table->string('cnpj', 14);
            $table->string('common_name', 255);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id', 'idx_certificates_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
