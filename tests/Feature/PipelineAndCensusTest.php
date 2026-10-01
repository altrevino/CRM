<?php

namespace Tests\Feature;

use App\Enums\PipelineStage;
use App\Livewire\Census\Index as CensusIndex;
use App\Livewire\Pipeline\Board;
use App\Models\Opportunity;
use App\Services\OpportunityService;
use Livewire\Livewire;
use Tests\TestCase;

class PipelineAndCensusTest extends TestCase
{
    public function test_cambio_de_etapa_queda_en_historial(): void
    {
        $user = $this->actingAsAdmin();
        $opportunity = Opportunity::factory()->create();

        app(OpportunityService::class)->changeStage($opportunity, PipelineStage::FollowUp);

        $this->assertSame(PipelineStage::FollowUp, $opportunity->fresh()->stage);
        $this->assertDatabaseHas('activity_logs', [
            'opportunity_id' => $opportunity->id,
            'event' => 'stage_changed',
            'user_id' => $user->id,
            'description' => 'Prospecto → Seguimiento',
        ]);
    }

    public function test_kanban_mueve_tarjetas_entre_columnas(): void
    {
        $this->actingAsUser();
        $opportunity = Opportunity::factory()->create();

        Livewire::test(Board::class)->call('handleSort', $opportunity->id, 0, 'pendiente_anticipo');

        $this->assertSame(PipelineStage::PendingDeposit, $opportunity->fresh()->stage);
    }

    public function test_kanban_pide_confirmacion_para_perdido(): void
    {
        $this->actingAsUser();
        $opportunity = Opportunity::factory()->create();

        $component = Livewire::test(Board::class)
            ->call('handleSort', $opportunity->id, 0, 'perdido')
            ->assertSet('confirmingLost', true);

        $this->assertSame(PipelineStage::Prospect, $opportunity->fresh()->stage);

        $component->set('lostReason', 'Precio')->call('confirmLost');
        $this->assertSame(PipelineStage::Lost, $opportunity->fresh()->stage);
        $this->assertSame('Precio', $opportunity->fresh()->lost_reason);
    }

    public function test_censo_confirmado_con_fecha_aparece_en_censos_programados(): void
    {
        $this->actingAsAdmin();
        $scheduled = Opportunity::factory()->stage(PipelineStage::Confirmed)->create(['census_date' => today()->addDays(10)]);

        $this->assertTrue(Opportunity::scheduled()->whereKey($scheduled->id)->exists());

        Livewire::test(CensusIndex::class)
            ->set('mode', 'lista')
            ->assertSee($scheduled->ranch->name);
    }

    public function test_oportunidad_sin_fecha_no_aparece_en_censos_programados(): void
    {
        $this->actingAsAdmin();
        $withoutDate = Opportunity::factory()->stage(PipelineStage::Confirmed)->create(['census_date' => null]);
        $otherStage = Opportunity::factory()->stage(PipelineStage::PendingDeposit)->create(['census_date' => today()->addDays(5)]);

        $this->assertFalse(Opportunity::scheduled()->whereKey($withoutDate->id)->exists());
        $this->assertFalse(Opportunity::scheduled()->whereKey($otherStage->id)->exists());

        Livewire::test(CensusIndex::class)
            ->set('mode', 'lista')
            ->assertDontSee($withoutDate->ranch->name)
            ->assertDontSee($otherStage->ranch->name);
    }

    public function test_censo_aparece_en_el_calendario_del_mes(): void
    {
        $this->actingAsAdmin();
        $date = today()->addMonth()->startOfMonth()->addDays(9);
        $opportunity = Opportunity::factory()->stage(PipelineStage::Confirmed)->create(['census_date' => $date]);

        Livewire::test(CensusIndex::class)
            ->set('month', $date->format('Y-m'))
            ->assertSee($opportunity->ranch->name);
    }

    public function test_cambio_de_fecha_de_censo_se_registra(): void
    {
        $this->actingAsAdmin();
        $opportunity = Opportunity::factory()->create();
        $opportunity->update(['census_date' => '2026-11-20']);

        $this->assertDatabaseHas('activity_logs', [
            'opportunity_id' => $opportunity->id,
            'event' => 'census_date_changed',
            'description' => 'Fecha de censo asignada: 20/11/2026',
        ]);
    }
}
