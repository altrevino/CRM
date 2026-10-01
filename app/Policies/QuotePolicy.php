<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class QuotePolicy extends RecordPolicy
{
    /** Las cotizaciones aceptadas, rechazadas o reemplazadas no se editan: se crea otra versión. */
    public function update(User $user, Model $quote): bool
    {
        return parent::update($user, $quote) && $quote->status->isEditable();
    }
}
