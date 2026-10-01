<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Models\Client;
use App\Services\ActivityLogger;

class ClientObserver
{
    private const FIELDS = ['name' => 'nombre', 'phone' => 'teléfono', 'notes' => 'notas'];

    public function created(Client $client): void
    {
        ActivityLogger::log(ActivityEvent::ClientCreated, $client, "Cliente {$client->name} creado");
    }

    public function updated(Client $client): void
    {
        $changed = array_intersect_key(self::FIELDS, $client->getChanges());
        if ($changed) {
            ActivityLogger::log(ActivityEvent::ClientUpdated, $client, 'Datos actualizados: '.implode(', ', $changed));
        }
    }

    public function deleted(Client $client): void
    {
        ActivityLogger::log(ActivityEvent::ClientDeleted, $client, "Cliente {$client->name} eliminado");
    }
}
