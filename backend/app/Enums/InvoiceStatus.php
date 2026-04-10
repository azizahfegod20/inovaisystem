<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case AUTHORIZED = 'authorized';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case REPLACED = 'replaced';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::AUTHORIZED => 'Autorizada',
            self::REJECTED => 'Rejeitada',
            self::CANCELLED => 'Cancelada',
            self::REPLACED => 'Substituída',
            self::ERROR => 'Erro',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::AUTHORIZED, self::CANCELLED, self::REPLACED, self::REJECTED]);
    }
}
