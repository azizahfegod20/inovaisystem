<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'service_id',
        'user_id',
        'status',
        'id_dps',
        'dps_number',
        'dps_serie',
        'chave_acesso',
        'numero_nfse',
        'valor_servico',
        'valor_deducoes',
        'valor_desconto',
        'valor_liquido',
        'aliquota_iss',
        'valor_iss',
        'iss_retido',
        'valor_ir',
        'valor_csll',
        'valor_cofins',
        'valor_pis',
        'valor_inss',
        'descricao_servico',
        'xml_sent_path',
        'xml_response_path',
        'pdf_path',
        'data_emissao',
        'data_cancelamento',
        'motivo_cancelamento',
        'invoice_replaced_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'valor_servico' => 'decimal:2',
            'valor_deducoes' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_liquido' => 'decimal:2',
            'aliquota_iss' => 'decimal:4',
            'valor_iss' => 'decimal:2',
            'iss_retido' => 'boolean',
            'valor_ir' => 'decimal:2',
            'valor_csll' => 'decimal:2',
            'valor_cofins' => 'decimal:2',
            'valor_pis' => 'decimal:2',
            'valor_inss' => 'decimal:2',
            'dps_number' => 'integer',
            'numero_nfse' => 'integer',
            'data_emissao' => 'datetime',
            'data_cancelamento' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replacedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_replaced_id');
    }

    public function replacements(): HasMany
    {
        return $this->hasMany(Invoice::class, 'invoice_replaced_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
