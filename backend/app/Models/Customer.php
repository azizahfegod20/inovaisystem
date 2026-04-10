<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'tipo_documento',
        'documento',
        'razao_social',
        'nome_fantasia',
        'inscricao_municipal',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'codigo_ibge',
        'uf',
        'cep',
        'email',
        'telefone',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isCnpj(): bool
    {
        return $this->tipo_documento === '2';
    }

    public function isCpf(): bool
    {
        return $this->tipo_documento === '1';
    }
}
