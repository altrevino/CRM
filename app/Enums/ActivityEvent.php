<?php

namespace App\Enums;

enum ActivityEvent: string
{
    case ClientCreated = 'client_created';
    case ClientUpdated = 'client_updated';
    case ClientDeleted = 'client_deleted';
    case RanchCreated = 'ranch_created';
    case RanchUpdated = 'ranch_updated';
    case RanchDeleted = 'ranch_deleted';
    case OpportunityCreated = 'opportunity_created';
    case OpportunityUpdated = 'opportunity_updated';
    case OpportunityDeleted = 'opportunity_deleted';
    case StageChanged = 'stage_changed';
    case CensusDateChanged = 'census_date_changed';
    case QuoteCreated = 'quote_created';
    case QuoteUpdated = 'quote_updated';
    case QuoteSent = 'quote_sent';
    case QuoteAccepted = 'quote_accepted';
    case QuoteRejected = 'quote_rejected';
    case QuoteReplaced = 'quote_replaced';
    case QuoteDeleted = 'quote_deleted';
    case PaymentRegistered = 'payment_registered';
    case PaymentUpdated = 'payment_updated';
    case PaymentDeleted = 'payment_deleted';
    case TaskCreated = 'task_created';
    case TaskCompleted = 'task_completed';
    case TaskCancelled = 'task_cancelled';
    case TaskReopened = 'task_reopened';
    case CommentAdded = 'comment_added';
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case SettingsUpdated = 'settings_updated';

    public function label(): string
    {
        return match ($this) {
            self::ClientCreated => 'Cliente creado',
            self::ClientUpdated => 'Cliente editado',
            self::ClientDeleted => 'Cliente eliminado',
            self::RanchCreated => 'Rancho creado',
            self::RanchUpdated => 'Rancho editado',
            self::RanchDeleted => 'Rancho eliminado',
            self::OpportunityCreated => 'Servicio creado',
            self::OpportunityUpdated => 'Servicio editado',
            self::OpportunityDeleted => 'Servicio eliminado',
            self::StageChanged => 'Etapa cambiada',
            self::CensusDateChanged => 'Fecha de censo modificada',
            self::QuoteCreated => 'Cotización creada',
            self::QuoteUpdated => 'Cotización editada',
            self::QuoteSent => 'Cotización enviada',
            self::QuoteAccepted => 'Cotización aceptada',
            self::QuoteRejected => 'Cotización rechazada',
            self::QuoteReplaced => 'Cotización reemplazada',
            self::QuoteDeleted => 'Cotización eliminada',
            self::PaymentRegistered => 'Pago registrado',
            self::PaymentUpdated => 'Pago editado',
            self::PaymentDeleted => 'Pago eliminado',
            self::TaskCreated => 'Tarea creada',
            self::TaskCompleted => 'Tarea completada',
            self::TaskCancelled => 'Tarea cancelada',
            self::TaskReopened => 'Tarea reabierta',
            self::CommentAdded => 'Comentario',
            self::UserCreated => 'Usuario creado',
            self::UserUpdated => 'Usuario editado',
            self::SettingsUpdated => 'Configuración actualizada',
        };
    }

    /** Nombre del ícono (ver componente x-icon). */
    public function icon(): string
    {
        return match ($this) {
            self::ClientCreated, self::ClientUpdated, self::ClientDeleted, self::UserCreated, self::UserUpdated => 'user',
            self::RanchCreated, self::RanchUpdated, self::RanchDeleted => 'map',
            self::OpportunityCreated, self::OpportunityUpdated, self::OpportunityDeleted => 'briefcase',
            self::StageChanged => 'arrows',
            self::CensusDateChanged => 'calendar',
            self::QuoteCreated, self::QuoteUpdated, self::QuoteSent, self::QuoteAccepted,
            self::QuoteRejected, self::QuoteReplaced, self::QuoteDeleted => 'document',
            self::PaymentRegistered, self::PaymentUpdated, self::PaymentDeleted => 'cash',
            self::TaskCreated, self::TaskCompleted, self::TaskCancelled, self::TaskReopened => 'check',
            self::CommentAdded => 'chat',
            self::SettingsUpdated => 'cog',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::ClientDeleted, self::RanchDeleted, self::OpportunityDeleted, self::QuoteDeleted,
            self::PaymentDeleted, self::QuoteRejected, self::TaskCancelled => 'bg-rose-100 text-rose-600',
            self::QuoteAccepted, self::PaymentRegistered, self::TaskCompleted => 'bg-emerald-100 text-emerald-700',
            self::StageChanged, self::CensusDateChanged => 'bg-blue-100 text-blue-700',
            self::CommentAdded => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
