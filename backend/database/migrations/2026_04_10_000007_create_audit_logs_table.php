<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation', 30);
            $table->text('payload_summary');
            $table->string('result', 10);
            $table->string('error_code', 20)->nullable();
            $table->string('ip_address', 45);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at'], 'idx_audit_company_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
