<?php

namespace App\Enums;

enum PipelineStage: string
{
    case Prospect = 'prospecto';
    case QuoteSent = 'cotizacion_enviada';
    case FollowUp = 'seguimiento';
    case PendingDeposit = 'pendiente_anticipo';
    case Confirmed = 'confirmado';
    case CensusDone = 'censo_realizado';
    case Lost = 'perdido';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospecto',
            self::QuoteSent => 'Cotización enviada',
            self::FollowUp => 'Seguimiento',
            self::PendingDeposit => 'Pendiente anticipo',
            self::Confirmed => 'Confirmado',
            self::CensusDone => 'Censo realizado',
            self::Lost => 'Perdido',
        };
    }

    /** Clases Tailwind del badge. Única fuente de color por etapa en toda la app. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Prospect => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::QuoteSent => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::FollowUp => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::PendingDeposit => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::Confirmed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::CensusDone => 'bg-emerald-700 text-white ring-emerald-700',
            self::Lost => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::Prospect => 'bg-slate-400',
            self::QuoteSent => 'bg-sky-400',
            self::FollowUp => 'bg-amber-400',
            self::PendingDeposit => 'bg-blue-600',
            self::Confirmed => 'bg-emerald-500',
            self::CensusDone => 'bg-emerald-800',
            self::Lost => 'bg-rose-400',
        };
    }

    /** Color hexadecimal para gráficas (mismo tono que el badge). */
    public function hex(): string
    {
        return match ($this) {
            self::Prospect => '#94a3b8',
            self::QuoteSent => '#38bdf8',
            self::FollowUp => '#fbbf24',
            self::PendingDeposit => '#2563eb',
            self::Confirmed => '#10b981',
            self::CensusDone => '#065f46',
            self::Lost => '#fb7185',
        };
    }

    public function order(): int
    {
        return array_search($this, self::cases(), true);
    }

    /** Oportunidades en negociación (forman el pipeline potencial). */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Prospect, self::QuoteSent, self::FollowUp, self::PendingDeposit];
    }

    /** Etapas que cuentan como venta cerrada. @return list<self> */
    public static function won(): array
    {
        return [self::Confirmed, self::CensusDone];
    }

    /** @return list<string> */
    public static function values(array $cases): array
    {
        return array_map(fn (self $c) => $c->value, $cases);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
