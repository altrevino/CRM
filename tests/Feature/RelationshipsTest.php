<?php

namespace Tests\Feature;

use App\Livewire\Forms\RanchForm;
use App\Models\Client;
use App\Models\MexicanState;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\Ranch;
use App\Services\QuoteService;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    public function test_un_cliente_puede_tener_varios_ranchos(): void
    {
        $client = Client::factory()->create();
        Ranch::factory()->count(3)->for($client)->create();

        $this->assertCount(3, $client->ranches);
    }

    public function test_un_rancho_puede_tener_varias_oportunidades(): void
    {
        $ranch = Ranch::factory()->create();
        Opportunity::factory()->count(3)->for($ranch)->create();

        $this->assertCount(3, $ranch->opportunities);
        $this->assertCount(3, $ranch->client->opportunities);
    }

    public function test_una_oportunidad_puede_tener_varias_cotizaciones(): void
    {
        $this->actingAsAdmin();
        $opportunity = Opportunity::factory()->create();
        $service = app(QuoteService::class);

        $first = $service->create($opportunity, $this->quoteData());
        $service->create($opportunity, $this->quoteData());
        $service->createVersion($first, $this->quoteData());

        $this->assertCount(3, $opportunity->quotes);
    }

    public function test_editar_rancho_con_campos_opcionales_vacios(): void
    {
        $this->actingAsUser();
        $ranch = Ranch::factory()->create([
            'municipality' => null, 'maps_url' => null, 'notes' => null,
            'fence_type' => null, 'total_hectares' => null, 'km_round_trip' => null, 'state_id' => null,
        ]);

        Livewire::test(RanchForm::class)
            ->call('open', $ranch->id)
            ->assertSet('showModal', true)
            ->assertSet('notes', '')
            ->set('name', 'Rancho Renombrado')
            ->set('municipality', 'Anáhuac')
            ->set('state_id', (string) MexicanState::first()->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Rancho Renombrado', $ranch->fresh()->name);
    }

    public function test_un_rancho_no_puede_existir_sin_cliente(): void
    {
        $this->expectException(QueryException::class);

        Ranch::create(['name' => 'Rancho Huérfano', 'municipality' => 'Anáhuac']);
    }

    public function test_una_cotizacion_no_puede_existir_sin_oportunidad(): void
    {
        $this->expectException(QueryException::class);

        Quote::create([
            'folio' => 1, 'issued_at' => today(), 'hectares' => 100,
            'service_amount' => 1000, 'apply_vat' => false,
        ]);
    }

    private function quoteData(): array
    {
        return ['issued_at' => today(), 'hectares' => 1000, 'service_amount' => 50000, 'logistics_amount' => 5000, 'apply_vat' => true];
    }
}
