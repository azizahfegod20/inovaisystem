<?php

namespace App\Enums;

enum AuditOperation: string
{
    case INVOICE_CREATED = 'invoice_created';
    case INVOICE_SENT = 'invoice_sent';
    case INVOICE_AUTHORIZED = 'invoice_authorized';
    case INVOICE_REJECTED = 'invoice_rejected';
    case INVOICE_CANCELLED = 'invoice_cancelled';
    case INVOICE_REPLACED = 'invoice_replaced';
    case CERTIFICATE_UPLOADED = 'certificate_uploaded';
    case CERTIFICATE_DELETED = 'certificate_deleted';
    case COMPANY_CREATED = 'company_created';
    case COMPANY_UPDATED = 'company_updated';
    case MEMBER_ADDED = 'member_added';
    case MEMBER_REMOVED = 'member_removed';
    case MEMBER_ROLE_CHANGED = 'member_role_changed';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE_CREATED => 'NFS-e Criada',
            self::INVOICE_SENT => 'NFS-e Enviada ao ADN',
            self::INVOICE_AUTHORIZED => 'NFS-e Autorizada',
            self::INVOICE_REJECTED => 'NFS-e Rejeitada',
            self::INVOICE_CANCELLED => 'NFS-e Cancelada',
            self::INVOICE_REPLACED => 'NFS-e Substituída',
            self::CERTIFICATE_UPLOADED => 'Certificado Enviado',
            self::CERTIFICATE_DELETED => 'Certificado Removido',
            self::COMPANY_CREATED => 'Empresa Cadastrada',
            self::COMPANY_UPDATED => 'Empresa Atualizada',
            self::MEMBER_ADDED => 'Membro Adicionado',
            self::MEMBER_REMOVED => 'Membro Removido',
            self::MEMBER_ROLE_CHANGED => 'Permissão Alterada',
        };
    }
}
