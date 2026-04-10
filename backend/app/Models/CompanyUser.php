<?php

namespace App\Models;

use App\Enums\CompanyRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUser extends Pivot
{
    protected $table = 'company_user';

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'company_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => CompanyRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
