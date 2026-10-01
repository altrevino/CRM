<?php

namespace App\Enums;

/**
 * Roles del sistema. Para agregar un rol nuevo basta con un nuevo caso
 * y su lista de permisos; las policies consultan permisos, no roles.
 */
enum UserRole: string
{
    case Admin = 'administrador';
    case User = 'usuario';

    public const PERMISSIONS = [
        'records.manage' => 'Crear y editar registros',
        'records.delete' => 'Eliminar registros',
        'users.manage' => 'Administrar usuarios',
        'settings.manage' => 'Administrar configuración y catálogos',
    ];

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::User => 'Usuario',
        };
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => array_keys(self::PERMISSIONS),
            self::User => ['records.manage'],
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
