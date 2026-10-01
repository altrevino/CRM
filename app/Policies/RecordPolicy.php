<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Política base para registros del CRM: trabajar requiere records.manage, eliminar records.delete. */
abstract class RecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('records.manage');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermission('records.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('records.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission('records.manage');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission('records.delete');
    }
}
