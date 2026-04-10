<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'inscricao_municipal',
        'inscricao_estadual',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'codigo_ibge',
        'uf',
        'cep',
        'telefone',
        'email',
        'regime_tributario',
        'reg_esp_trib',
        'dps_serie',
        'dps_next_number',
        'ambiente',
    ];

    protected function casts(): array
    {
        return [
            'regime_tributario' => 'integer',
            'reg_esp_trib' => 'integer',
            'dps_next_number' => 'integer',
            'ambiente' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
