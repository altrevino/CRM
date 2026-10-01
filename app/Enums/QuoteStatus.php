<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'borrador';
    case Sent = 'enviada';
    case Accepted = 'aceptada';
    case Rejected = 'rechazada';
    case Replaced = 'reemplazada';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
            self::Replaced => 'Reemplazada',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Sent => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::Accepted => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Rejected => 'bg-rose-50 text-rose-700 ring-rose-200',
            self::Replaced => 'bg-zinc-100 text-zinc-500 ring-zinc-200 line-through',
        };
    }

    /** Estados en los que la cotización todavía puede editarse. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Sent], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
