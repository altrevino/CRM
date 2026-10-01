<?php

namespace App\Enums;

enum ServiceType: string
{
    case Complete = 'completo';
    case Representative = 'representativo';
    case Location = 'localizacion';

    public function label(): string
    {
        return match ($this) {
            self::Complete => 'Completo',
            self::Representative => 'Representativo',
            self::Location => 'Localización',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
