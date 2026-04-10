<?php

namespace App\Models;

use App\Enums\AuditOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'invoice_id',
        'operation',
        'payload_summary',
        'result',
        'error_code',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'operation' => AuditOperation::class,
            'payload_summary' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
