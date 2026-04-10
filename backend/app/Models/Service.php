<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'codigo_lc116',
        'codigo_nbs',
        'descricao',
        'aliquota_iss',
        'is_favorite',
    ];

    protected function casts(): array
    {
        return [
            'aliquota_iss' => 'decimal:4',
            'is_favorite' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
