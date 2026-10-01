<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pendiente';
    case Completed = 'completada';
    case Cancelled = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Cancelled => 'bg-zinc-100 text-zinc-500 ring-zinc-200',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
