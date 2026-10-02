<?php

namespace Tests\Feature;

use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Livewire\Forms\QuoteForm;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\QuoteService;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    private QuoteService $service;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
        $this->service = app(QuoteService::class);
        $this->opportunity = Opportunity::factory()->create();
    }

    public function test_calcula_iva_sobre_servicio_mas_logistica(): void
    {
        $quote = $this->service->create($this->opportunity, [
            'issued_at' => today(), 'hectares' => 1000,
            'service_amount' => 60000, 'logistics_amount' => 15000, 'apply_vat' => true,
        ]);

        $this->assertEquals(75000, (float) $quote->subtotal);
        $this->assertEquals(16, (float) $quote->vat_rate);
        $this->assertEquals(12000, (float) $quote->vat_amount);
        $this->assertEquals(87000, (float) $quote->total);
    }

    public function test_cotizacion_sin_iva_tiene_iva_cero_y_total_igual_al_subtotal(): void
    {
        $quote = $this->service->create($this->opportunity, [
            'issued_at' => today(), 'hectares' => 1000,
            'service_amount' => 60000, 'logistics_amount' => 15000, 'apply_vat' => false,
        ]);

        $this->assertEquals(0, (float) $quote->vat_amount);
        $this->assertEquals(0, (float) $quote->vat_rate);
        $this->assertEquals(75000, (float) $quote->total);
        $this->assertSame('Sin IVA', $quote->vatLabel());
    }

    public function test_conserva_el_iva_historico_si_cambia_la_configuracion(): void
    {
        $quote = $this->service->create($this->opportunity, [
            'issued_at' => today(), 'hectares' => 100, 'service_amount' => 10000, 'apply_vat' => true,
        ]);

        Setting::set('vat_rate', 8);
        $newQuote = $this->service->create($this->opportunity, [
            'issued_at' => today(), 'hectares' => 100, 'service_amount' => 10000, 'apply_vat' => true,
        ]);

        $this->assertEquals(16, (float) $quote->fresh()->vat_rate);
        $this->assertEquals(11600, (float) $quote->fresh()->total);
        $this->assertEquals(8, (float) $newQuote->vat_rate);
    }

    public function test_el_formulario_obliga_a_elegir_con_o_sin_iva(): void
    {
        Livewire::test(QuoteForm::class)
            ->call('open', $this->opportunity->id)
            ->set('service_amount', '10000')
            ->call('save')
            ->assertHasErrors(['apply_vat' => 'required']);

        $this->assertSame(0, Quote::count());
    }

    public function test_versiones_usan_el_mismo_folio_y_reemplazan_la_anterior(): void
    {
        $v1 = $this->service->create($this->opportunity, $this->data());
        $this->service->markSent($v1);
        $v2 = $this->service->createVersion($v1, $this->data());
        $v3 = $this->service->createVersion($v2, $this->data());

        $this->assertSame('COT-'.$v1->folio, $v1->number);
        $this->assertSame('COT-'.$v1->folio.' V2', $v2->number);
        $this->assertSame('COT-'.$v1->folio.' V3', $v3->number);
        $this->assertSame(QuoteStatus::Replaced, $v1->fresh()->status);
        $this->assertSame(QuoteStatus::Replaced, $v2->fresh()->status);
    }

    public function test_solo_una_cotizacion_puede_estar_aceptada(): void
    {
        $a = $this->service->create($this->opportunity, $this->data());
        $b = $this->service->create($this->opportunity, $this->data());

        $this->service->accept($a);
        $this->service->accept($b);

        $this->assertSame(QuoteStatus::Replaced, $a->fresh()->status);
        $this->assertSame(QuoteStatus::Accepted, $b->fresh()->status);
        $this->assertSame(1, Quote::where('opportunity_id', $this->opportunity->id)->where('status', 'aceptada')->count());
    }

    public function test_la_base_de_datos_impide_dos_aceptadas(): void
    {
        $a = $this->service->create($this->opportunity, $this->data());
        $b = $this->service->create($this->opportunity, $this->data());
        // Saltando el servicio: el índice único (opportunity_id, accepted_lock) lo impide.
        $a->update(['status' => QuoteStatus::Accepted]);

        $this->expectException(QueryException::class);
        $b->update(['status' => QuoteStatus::Accepted]);
    }

    public function test_aceptar_avanza_la_etapa_y_sincroniza_hectareas(): void
    {
        $quote = $this->service->create($this->opportunity, [...$this->data(), 'hectares' => 1234]);
        $this->service->accept($quote);

        $opportunity = $this->opportunity->fresh();
        $this->assertSame(PipelineStage::PendingDeposit, $opportunity->stage);
        $this->assertEquals(1234, (float) $opportunity->quoted_hectares);
    }

    public function test_marcar_enviada_avanza_prospecto(): void
    {
        $quote = $this->service->create($this->opportunity, $this->data());
        $this->service->markSent($quote);

        $this->assertSame(PipelineStage::QuoteSent, $this->opportunity->fresh()->stage);
        $this->assertNotNull($quote->fresh()->sent_at);
    }

    public function test_cotizacion_aceptada_no_se_edita(): void
    {
        $quote = $this->service->create($this->opportunity, $this->data());
        $this->service->accept($quote);

        $this->expectException(ValidationException::class);
        $this->service->update($quote->fresh(), $this->data());
    }

    public function test_eliminar_es_soft_delete_y_queda_en_historial(): void
    {
        $quote = $this->service->create($this->opportunity, $this->data());
        $this->service->delete($quote);

        $this->assertSoftDeleted($quote);
        $this->assertDatabaseHas('activity_logs', ['event' => 'quote_deleted', 'subject_id' => $quote->id]);
    }

    public function test_eliminar_la_aceptada_permite_aceptar_otra(): void
    {
        $accepted = $this->service->create($this->opportunity, $this->data());
        $this->service->accept($accepted);
        $this->service->delete($accepted->fresh());

        $other = $this->service->create($this->opportunity, $this->data());
        $this->service->accept($other->fresh());

        $this->assertSame(QuoteStatus::Accepted, $other->fresh()->status);
        $this->assertNull(Quote::withTrashed()->find($accepted->id)->accepted_lock);
    }

    public function test_aceptar_libera_candados_huerfanos_de_datos_previos(): void
    {
        // Simula el dato dañado que dejaba la versión anterior al eliminar una aceptada.
        $old = $this->service->create($this->opportunity, $this->data());
        $old->forceFill(['status' => QuoteStatus::Replaced, 'accepted_lock' => 1])->saveQuietly();
        $old->delete();

        $new = $this->service->create($this->opportunity, $this->data());
        $this->service->accept($new->fresh());

        $this->assertSame(QuoteStatus::Accepted, $new->fresh()->status);
    }

    private function data(): array
    {
        return ['issued_at' => today(), 'hectares' => 1000, 'service_amount' => 50000, 'logistics_amount' => 5000, 'apply_vat' => true];
    }
}
