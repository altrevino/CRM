<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage');
    }

    /** Nadie puede desactivarse a sí mismo (evita quedarse sin administradores). */
    public function deactivate(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage') && $user->isNot($model);
    }
}
