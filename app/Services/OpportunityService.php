<?php

namespace App\Services;

use App\Enums\PipelineStage;
use App\Models\Opportunity;

class OpportunityService
{
    public function changeStage(Opportunity $opportunity, PipelineStage $stage, ?string $lostReason = null): void
    {
        if ($opportunity->stage === $stage) {
            return;
        }

        $opportunity->stage = $stage;
        $opportunity->lost_reason = $stage === PipelineStage::Lost ? ($lostReason ?: null) : null;
        $opportunity->save();
    }

    /** Avanza la etapa solo si la oportunidad está antes de $stage (nunca retrocede ni revive perdidas). */
    public function advanceTo(Opportunity $opportunity, PipelineStage $stage): void
    {
        if ($opportunity->stage === PipelineStage::Lost) {
            return;
        }

        if ($opportunity->stage->order() < $stage->order()) {
            $this->changeStage($opportunity, $stage);
        }
    }
}
