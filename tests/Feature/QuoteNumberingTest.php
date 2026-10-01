<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Livewire\Forms\QuoteAdjustForm;
use App\Livewire\Forms\QuoteForm;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Services\MetricsService;
use App\Services\QuoteService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/** Números de cotización capturados a mano al migrar desde otro sistema. */
class QuoteNumberingTest extends TestCase
{
    private QuoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
        $this->service = app(QuoteService::class);
    }

    public function test_crea_con_numero_manual_y_los_nuevos_siguen_consecutivos(): void
    {
        $opportunity = Opportunity::factory()->create();

        $old = $this->service->create($opportunity, [...$this->data(), 'folio' => 145, 'version' => 2]);
        $new = $this->service->create($opportunity, $this->data());

        $this->assertSame('COT-145 V2', $old->number);
        $this->assertSame('COT-146', $new->number);
    }

    public function test_no_permite_numero_repetido_ni_folio_de_otro_servicio(): void
    {
        $a = Opportunity::factory()->create();
        $b = Opportunity::factory()->create();
        $this->service->create($a, [...$this->data(), 'folio' => 145]);

        try {
            $this->service->create($a, [...$this->data(), 'folio' => 145]);
            $this->fail('Debió rechazar COT-145 repetida');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('COT-145 ya existe', $e->errors()['folio'][0]);
        }

        $this->expectException(ValidationException::class);
        $this->service->create($b, [...$this->data(), 'folio' => 145, 'version' => 3]);
    }

    public function test_formulario_acepta_folio_manual(): void
    {
        $opportunity = Opportunity::factory()->create();

        Livewire::test(QuoteForm::class)
            ->call('open', $opportunity->id)
            ->set('folio', '87')
            ->set('service_amount', '10000')
            ->set('apply_vat', 'no')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Quote::where('number', 'COT-87')->exists());
    }

    public function test_ajustar_folio_renumera_todas_las_versiones_aunque_este_aceptada(): void
    {
        $opportunity = Opportunity::factory()->create();
        $v1 = $this->service->create($opportunity, $this->data());
        $v2 = $this->service->createVersion($v1, $this->data());
        $this->service->accept($v2);

        Livewire::test(QuoteAdjustForm::class)
            ->call('open', $v2->id)
            ->set('folio', '312')
            ->set('accepted_at', '2025-03-10')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('COT-312', $v1->fresh()->number);
        $this->assertSame('COT-312 V2', $v2->fresh()->number);
        $this->assertSame(QuoteStatus::Accepted, $v2->fresh()->status);
        $this->assertSame('2025-03-10', $v2->fresh()->accepted_at->toDateString());
        $this->assertDatabaseHas('activity_logs', ['event' => 'quote_updated', 'subject_id' => $v2->id]);
    }

    public function test_fecha_de_aceptacion_historica_mueve_la_venta_de_mes(): void
    {
        $opportunity = Opportunity::factory()->create(['stage' => 'confirmado']);
        $quote = $this->service->create($opportunity, [...$this->data(), 'apply_vat' => false]);
        $this->service->accept($quote);
        $this->service->adjust($quote->fresh(), ['folio' => $quote->folio, 'version' => 1, 'accepted_at' => '2025-03-10']);

        $sales = app(MetricsService::class)->salesByMonth(2025);
        $this->assertEquals(55000, $sales[2]);
    }

    public function test_ajuste_rechaza_numero_ocupado(): void
    {
        $opportunity = Opportunity::factory()->create();
        $this->service->create($opportunity, [...$this->data(), 'folio' => 10]);
        $other = $this->service->create($opportunity, [...$this->data(), 'folio' => 11]);

        Livewire::test(QuoteAdjustForm::class)
            ->call('open', $other->id)
            ->set('folio', '10')
            ->call('save')
            ->assertHasErrors('folio');

        $this->assertSame('COT-11', $other->fresh()->number);
    }

    private function data(): array
    {
        return ['issued_at' => today(), 'hectares' => 1000, 'service_amount' => 50000, 'logistics_amount' => 5000, 'apply_vat' => true];
    }
}
