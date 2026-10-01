<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Models\Ranch;
use App\Models\Task;
use App\Services\ActivityLogger;

class RanchObserver
{
    private const FIELDS = [
        'client_id' => 'cliente',
        'name' => 'nombre',
        'municipality' => 'municipio',
        'state_id' => 'estado',
        'maps_url' => 'ubicación',
        'km_round_trip' => 'kilómetros',
        'fence_type' => 'cerca',
        'total_hectares' => 'superficie',
        'notes' => 'notas',
    ];

    public function created(Ranch $ranch): void
    {
        ActivityLogger::log(ActivityEvent::RanchCreated, $ranch, "Rancho {$ranch->name} creado");
    }

    public function updated(Ranch $ranch): void
    {
        if ($ranch->wasChanged('client_id')) {
            Task::where('ranch_id', $ranch->id)->update(['client_id' => $ranch->client_id]);
        }

        $changed = array_intersect_key(self::FIELDS, $ranch->getChanges());
        if ($changed) {
            ActivityLogger::log(ActivityEvent::RanchUpdated, $ranch, 'Rancho actualizado: '.implode(', ', $changed));
        }
    }

    public function deleted(Ranch $ranch): void
    {
        ActivityLogger::log(ActivityEvent::RanchDeleted, $ranch, "Rancho {$ranch->name} eliminado");
    }
}
