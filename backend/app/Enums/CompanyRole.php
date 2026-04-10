<?php

namespace App\Enums;

enum CompanyRole: string
{
    case ADMIN = 'admin';
    case ACCOUNTANT = 'contador';
    case OPERATOR = 'operador';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::ACCOUNTANT => 'Contador',
            self::OPERATOR => 'Operador',
        };
    }

    public function canManageMembers(): bool
    {
        return $this === self::ADMIN;
    }

    public function canEmitInvoice(): bool
    {
        return in_array($this, [self::ADMIN, self::ACCOUNTANT, self::OPERATOR]);
    }

    public function canManageCertificates(): bool
    {
        return in_array($this, [self::ADMIN, self::ACCOUNTANT]);
    }
}
