<?php

namespace App\Enums;

enum FenceType: string
{
    case High = 'alta';
    case Low = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::High => 'Cerca alta',
            self::Low => 'Cerca baja',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
