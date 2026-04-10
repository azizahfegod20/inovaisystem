<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $appends = ['is_expired', 'is_expiring_soon'];

    protected $fillable = [
        'company_id',
        'pfx_content',
        'pfx_password',
        'cnpj',
        'common_name',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pfx_content' => 'encrypted',
            'pfx_password' => 'encrypted',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_to->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return ! $this->isExpired() && $this->valid_to->diffInDays(now()) <= $days;
    }

    protected function isExpiredAttribute(): Attribute
    {
        return Attribute::get(fn () => $this->isExpired());
    }

    protected function isExpiringSoonAttribute(): Attribute
    {
        return Attribute::get(fn () => $this->isExpiringSoon());
    }
}
