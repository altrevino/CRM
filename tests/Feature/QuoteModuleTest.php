<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Livewire\Quotes\Builder;
use App\Livewire\Quotes\Show;
use App\Livewire\Settings\QuotePricing;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\PriceList;
use App\Models\Quote;
use App\Models\Ranch;
use App\Services\QuotePdf;
use App\Services\QuoteService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/** Flujo de cotización rápida: tarifario versionado, constructor, revisiones, duplicado y PDF. */
class QuoteModuleTest extends TestCase
{
    private QuoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
        $this->service = app(QuoteService::class);
    }

    public function test_constructor_crea_cliente_rancho_y_cotizacion_con_el_caso_de_aceptacion(): void
    {
        $component = Livewire::test(Builder::class, ['quote' => null])
            ->call('newClient')
            ->set('client_name', 'Juan Pérez')
            ->set('client_phone', '81 1234 5678')
            ->set('ranch_name', 'Rancho Las Lomas')
            ->set('municipality', 'Anáhuac')
            ->set('hectares', '2000')
            ->set('one_way_km', '200')
            ->set('apply_vat', 'si');

        $preview = $component->instance()->breakdown['result'];
        $this->assertSame('154860.00', $preview['total']);

        $component->call('save', true)->assertHasNoErrors()->assertRedirect();

        $quote = Quote::sole();
        $this->assertSame(1, Client::count());
        $this->assertSame(1, Ranch::count());
        $this->assertSame('Rancho Las Lomas', $quote->opportunity->ranch->name);
        $this->assertEquals(400, (float) $quote->opportunity->ranch->km_round_trip);
        $this->assertEquals(127500, (float) $quote->service_amount);
        $this->assertEquals(6000, (float) $quote->logistics_amount);
        $this->assertEquals(133500, (float) $quote->subtotal);
        $this->assertEquals(21360, (float) $quote->vat_amount);
        $this->assertSame($preview['total'], (string) $quote->total);
        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame(PriceList::current()->id, $quote->price_list_id);
        $this->assertSame(1, $quote->pricing_snapshot['version']);
        $this->assertSame(today()->addDays(15)->toDateString(), $quote->valid_until->toDateString());
    }

    public function test_el_total_no_depende_del_navegador(): void
    {
        $opportunity = Opportunity::factory()->create();

        Livewire::test(Builder::class, ['quote' => null])
            ->call('selectClient', $opportunity->ranch->client_id)
            ->call('selectRanch', $opportunity->ranch_id)
            ->set('opportunity_id', $opportunity->id)
            ->set('hectares', '2000')
            ->set('one_way_km', '200')
            ->set('discount_type', 'amount')
            ->set('discount_value', '5000')
            ->set('discount_scope', 'travel')
            ->set('discount_reason', 'Ruta compartida')
            ->set('apply_vat', 'si')
            ->call('save')
            ->assertHasNoErrors();

        $quote = Quote::sole();
        $this->assertEquals(128500, (float) $quote->subtotal);
        $this->assertEquals(20560, (float) $quote->vat_amount);
        $this->assertEquals(149060, (float) $quote->total);
    }

    public function test_cambiar_el_tarifario_crea_una_version_y_no_altera_cotizaciones_emitidas(): void
    {
        $quote = $this->quote(['hectares' => 2000, 'one_way_km' => 200, 'apply_vat' => true]);
        $this->service->emit($quote);

        Livewire::test(QuotePricing::class)
            ->set('blocks.2.price', '70')       // > 1500–2000 sube de $55 a $70
            ->set('km_rate', '20')
            ->set('change_note', 'Ajuste 2027')
            ->call('save')
            ->assertHasNoErrors();

        $current = PriceList::current();
        $this->assertSame(2, $current->version);
        $this->assertSame(2, PriceList::count());

        $issued = $quote->fresh();
        $this->assertEquals(154860, (float) $issued->total);
        $this->assertSame(1, $issued->pricingVersion());
        $this->assertEquals(15, (float) $issued->pricing_snapshot['km_rate']);

        $this->get(route('quotes.pdf', $issued))->assertOk();
        $this->assertStringContainsString('$154,860', $this->pdfText($issued));

        $new = $this->quote(['hectares' => 2000, 'one_way_km' => 200, 'apply_vat' => false]);
        $this->assertEquals(135000, (float) $new->service_amount);  // 70,000 + 30,000 + 500 × 70
        $this->assertEquals(8000, (float) $new->logistics_amount);  // 400 km × $20
    }

    public function test_configuracion_rechaza_bloques_con_traslape_o_hueco(): void
    {
        Livewire::test(QuotePricing::class)
            ->set('blocks.1.up_to', '900')
            ->call('save')
            ->assertHasErrors('blocks.1.up_to');

        Livewire::test(QuotePricing::class)
            ->set('blocks.6.up_to', '8000')
            ->call('save')
            ->assertHasErrors('blocks.6.up_to');

        Livewire::test(QuotePricing::class)
            ->set('blocks.0.price', '-1')
            ->call('save')
            ->assertHasErrors('blocks.0.price');

        $this->assertSame(1, PriceList::count());
    }

    public function test_borrador_con_tarifa_anterior_avisa_y_exige_actualizar_o_motivo(): void
    {
        $draft = $this->quote(['hectares' => 2000, 'one_way_km' => 0, 'apply_vat' => false]);
        $this->newVersion(['minimum_price' => 40000]);

        $this->assertFalse($draft->fresh()->usesCurrentPricing());
        Livewire::test(Builder::class, ['quote' => $draft->fresh()])
            ->assertSee('Actualizar a tarifa vigente')
            ->assertSee('Tarifa v1');

        try {
            $this->service->emit($draft->fresh());
            $this->fail('Emitió con tarifa no vigente sin motivo');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pricing', $e->errors());
        }

        Livewire::test(Show::class, ['quote' => $draft->fresh()])
            ->call('openEmit')
            ->set('overrideReason', 'Precio respetado de propuesta de octubre')
            ->call('emit')
            ->assertHasNoErrors();

        $emitted = $draft->fresh();
        $this->assertSame(QuoteStatus::Issued, $emitted->status);
        $this->assertSame('Precio respetado de propuesta de octubre', $emitted->pricing_override_reason);
        $this->assertEquals(127500, (float) $emitted->total);
    }

    public function test_aceptar_borrador_previo_al_tarifario_pide_motivo_y_conserva_montos(): void
    {
        $legacy = $this->quote(['hectares' => 1000, 'one_way_km' => 0, 'apply_vat' => false]);
        $legacy->forceFill(['price_list_id' => null, 'pricing_snapshot' => null, 'service_amount' => 50000])->save();
        $legacy->refresh();
        $this->assertEquals(50000, (float) $legacy->total);

        Livewire::test(Show::class, ['quote' => $legacy])
            ->call('accept')
            ->assertSet('showEmit', true)
            ->assertSet('then', 'accept')
            ->assertSee('precios previos al tarifario')
            ->call('emit')
            ->assertHasErrors('pricing')
            ->set('overrideReason', 'Cotización migrada del sistema anterior')
            ->call('emit')
            ->assertHasNoErrors();

        $legacy->refresh();
        $this->assertSame(QuoteStatus::Accepted, $legacy->status);
        $this->assertEquals(50000, (float) $legacy->total);
        $this->assertSame('Cotización migrada del sistema anterior', $legacy->pricing_override_reason);

        $this->get(route('quotes.show', [$this->quote(['hectares' => 10, 'apply_vat' => false]), 'accion' => 'enviar']))->assertOk();
    }

    public function test_actualizar_borrador_a_tarifa_vigente_recalcula(): void
    {
        $draft = $this->quote(['hectares' => 400, 'one_way_km' => 10, 'apply_vat' => false]);
        $this->newVersion(['minimum_price' => 40000]);

        Livewire::test(Show::class, ['quote' => $draft])->call('refreshPricing');

        $draft->refresh();
        $this->assertTrue($draft->usesCurrentPricing());
        $this->assertEquals(40000, (float) $draft->service_amount);
        $this->assertEquals(40300, (float) $draft->total);
    }

    public function test_duplicar_usa_la_tarifa_vigente_sin_descuentos_ni_ajustes(): void
    {
        $original = $this->quote([
            'hectares' => 2000, 'one_way_km' => 200, 'apply_vat' => true,
            'route_adjusted' => true, 'route_km' => 300, 'route_adjust_reason' => 'Ruta compartida',
            'discount_type' => 'amount', 'discount_value' => 1000, 'discount_scope' => 'census', 'discount_reason' => 'Cliente recurrente',
        ]);
        $this->service->emit($original);
        $this->newVersion(['km_rate' => 20]);

        Livewire::withQueryParams(['duplicar' => $original->id])
            ->test(Builder::class, ['quote' => null])
            ->assertSet('mode', 'duplicate')
            ->assertSet('discount_type', '')
            ->assertSet('route_adjusted', false)
            ->assertSee('Precios actualizados')
            ->call('save')
            ->assertHasNoErrors();

        $copy = Quote::where('duplicated_from', $original->id)->sole();
        $this->assertNotSame($original->folio, $copy->folio);
        $this->assertSame($original->opportunity_id, $copy->opportunity_id);
        $this->assertSame(2, $copy->pricingVersion());
        $this->assertEquals(8000, (float) $copy->logistics_amount);
        $this->assertEquals(0, (float) $copy->discount_amount);
        $this->assertNull($copy->route_adjust_reason);
        $this->assertSame(QuoteStatus::Issued, $original->fresh()->status);
    }

    public function test_revision_conserva_la_anterior_y_la_marca_reemplazada(): void
    {
        $v1 = $this->quote(['hectares' => 1000, 'one_way_km' => 50, 'apply_vat' => true]);
        $this->service->markSent($v1);

        Livewire::withQueryParams(['revisar' => $v1->id])
            ->test(Builder::class, ['quote' => null])
            ->assertSet('mode', 'revision')
            ->set('hectares', '1200')
            ->call('save')
            ->assertHasNoErrors();

        $v2 = Quote::where('revision_of', $v1->id)->sole();
        $this->assertSame('COT-'.$v1->folio.' V2', $v2->number);
        $this->assertSame(QuoteStatus::Replaced, $v1->fresh()->status);
        $this->assertEquals(70000, (float) $v1->fresh()->service_amount);
        $this->assertEquals(82000, (float) $v2->service_amount);
    }

    public function test_cotizacion_emitida_no_se_edita(): void
    {
        $quote = $this->quote(['hectares' => 1000, 'one_way_km' => 0, 'apply_vat' => true]);
        $this->service->emit($quote);

        $this->get(route('quotes.edit', $quote))->assertForbidden();
        $this->expectException(ValidationException::class);
        $this->service->updateDraft($quote->fresh(), ['hectares' => 10, 'apply_vat' => true]);
    }

    public function test_pdf_se_descarga_con_nombre_y_total_de_la_cotizacion_sin_cambiar_estado(): void
    {
        $quote = $this->quote([
            'hectares' => 2000, 'one_way_km' => 200, 'apply_vat' => true,
            'discount_type' => 'amount', 'discount_value' => 5000, 'discount_scope' => 'travel', 'discount_reason' => 'MOTIVO-INTERNO-XYZ',
        ]);
        $quote->opportunity->ranch->update(['name' => 'Rancho Las Lomas']);
        $quote->opportunity->update(['service_type' => 'completo']);

        $response = $this->get(route('quotes.pdf', $quote));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('Espectro_Cotizacion_COT-'.$quote->folio.'_Rancho_Las_Lomas.pdf', $response->headers->get('content-disposition'));
        $this->assertLessThan(2 * 1024 * 1024, strlen($response->getContent()));

        $text = $this->pdfText($quote);
        $this->assertStringContainsString('$149,060', $text);
        $this->assertStringContainsString('$20,560', $text);
        $this->assertStringNotContainsString('MOTIVO-INTERNO-XYZ', $text);
        $this->assertStringContainsString('Anticipo 25% para reservar fecha', $text);
        $this->assertStringContainsString('LEVANTAMIENTO INTEGRAL', $text);
        $this->assertSame(QuoteStatus::Draft, $quote->fresh()->status);
        $this->assertNull($quote->fresh()->sent_at);
    }

    public function test_permisos_de_configuracion_y_pdf(): void
    {
        $quote = $this->quote(['hectares' => 1000, 'one_way_km' => 0, 'apply_vat' => true]);

        $this->actingAsUser();
        $this->get(route('settings.quotes'))->assertForbidden();
        $this->get(route('quotes.pdf', $quote))->assertOk();
        $this->get(route('quotes.create'))->assertOk();

        auth()->logout();
        $this->get(route('quotes.pdf', $quote))->assertRedirect(route('login'));
        $this->get(route('quotes.show', $quote))->assertRedirect(route('login'));
    }

    public function test_pantallas_del_modulo_responden(): void
    {
        $quote = $this->quote(['hectares' => 1000, 'one_way_km' => 30, 'apply_vat' => true]);

        $this->get(route('quotes.create'))->assertOk()->assertSee('Nueva cotización');
        $this->get(route('quotes.create', ['servicio' => $quote->opportunity_id]))->assertOk();
        $this->get(route('quotes.show', $quote))->assertOk()->assertSee($quote->number)->assertSee('Tarifa v1');
        $this->get(route('quotes.edit', $quote))->assertOk();
        $this->get(route('settings.quotes'))->assertOk()->assertSee('Tarifa v1');
        $this->get(route('quotes.index'))->assertOk()->assertSee('Tarifa v1');
    }

    private function quote(array $data): Quote
    {
        return $this->service->create(Opportunity::factory()->create(), $data);
    }

    private function newVersion(array $changes): PriceList
    {
        $current = PriceList::current();
        $attributes = collect($current->getAttributes())->except(['id', 'created_at', 'updated_at', 'blocks', 'business'])->all();
        PriceList::query()->toBase()->update(['is_current' => false]);

        return PriceList::create([...$attributes, 'blocks' => $current->blocks, 'business' => $current->business, 'version' => $current->version + 1, 'is_current' => true, 'activated_at' => now(), ...$changes]);
    }

    /** Texto del PDF generado (sin comprimir) para comparar montos con la pantalla. */
    private function pdfText(Quote $quote): string
    {
        $html = app(QuotePdf::class)->html($quote);

        return html_entity_decode(strip_tags($html));
    }
}
