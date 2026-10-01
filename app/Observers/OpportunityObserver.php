<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Enums\PipelineStage;
use App\Models\Opportunity;
use App\Services\ActivityLogger;

class OpportunityObserver
{
    private const FIELDS = [
        'service_type' => 'tipo de servicio',
        'quoted_hectares' => 'hectáreas cotizadas',
        'tentative_census_date' => 'fecha tentativa',
        'owner_id' => 'responsable',
        'notes' => 'notas',
        'ranch_id' => 'rancho',
    ];

    public function creating(Opportunity $opportunity): void
    {
        $opportunity->stage_changed_at ??= now();
    }

    public function updating(Opportunity $opportunity): void
    {
        if ($opportunity->isDirty('stage')) {
            $opportunity->stage_changed_at = now();
        }
    }

    public function created(Opportunity $opportunity): void
    {
        ActivityLogger::log(
            ActivityEvent::OpportunityCreated,
            $opportunity,
            "Servicio {$opportunity->service_type->label()} creado en etapa {$opportunity->stage->label()}"
        );
    }

    public function updated(Opportunity $opportunity): void
    {
        if ($opportunity->wasChanged('stage')) {
            $from = PipelineStage::from($opportunity->getOriginal('stage') instanceof PipelineStage
                ? $opportunity->getOriginal('stage')->value
                : $opportunity->getOriginal('stage'));
            $description = "{$from->label()} → {$opportunity->stage->label()}";
            if ($opportunity->stage === PipelineStage::Lost && $opportunity->lost_reason) {
                $description .= ". Motivo: {$opportunity->lost_reason}";
            }

            ActivityLogger::log(ActivityEvent::StageChanged, $opportunity, $description, [
                'from' => $from->value,
                'to' => $opportunity->stage->value,
            ]);
        }

        if ($opportunity->wasChanged('census_date')) {
            $old = $opportunity->getOriginal('census_date');
            $new = $opportunity->census_date;
            $description = match (true) {
                $old === null => 'Fecha de censo asignada: '.fecha($new),
                $new === null => 'Fecha de censo eliminada (era '.fecha($old).')',
                default => 'Fecha de censo: '.fecha($old).' → '.fecha($new),
            };

            ActivityLogger::log(ActivityEvent::CensusDateChanged, $opportunity, $description);
        }

        $changed = array_intersect_key(self::FIELDS, $opportunity->getChanges());
        if ($changed) {
            ActivityLogger::log(ActivityEvent::OpportunityUpdated, $opportunity, 'Servicio actualizado: '.implode(', ', $changed));
        }
    }

    public function deleted(Opportunity $opportunity): void
    {
        ActivityLogger::log(ActivityEvent::OpportunityDeleted, $opportunity, 'Servicio eliminado');
    }
}
