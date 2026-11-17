<?php

namespace Tests\Feature;

use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Livewire\Quotes\Show;
use App\Mail\QuoteClientResponded;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Services\QuoteService;
use App\Support\BusinessDays;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Link público de la cotización: vista, aceptar, rechazar, vigencia y privacidad. */
class PublicQuoteTest extends TestCase
{
    private QuoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->actingAsAdmin();
        $this->service = app(QuoteService::class);
    }

    private function issued(array $data = []): Quote
    {
        $opportunity = Opportunity::factory()->create(['service_type' => 'completo']);
        $quote = $this->service->create($opportunity, ['hectares' => 2000, 'one_way_km' => 200, 'apply_vat' => true, ...$data]);
        $this->service->emit($quote);

        return $quote->fresh();
    }

    private function asClient(): void
    {
        Auth::logout();
    }

    public function test_cliente_ve_la_cotizacion_con_desglose_y_se_registra_la_visita(): void
    {
        $quote = $this->issued();
        $url = $quote->publicUrl();
        $this->asClient();

        $this->get($url)->assertOk()
            ->assertSee($quote->number)
            ->assertSee('$154,860')
            ->assertSee('Aceptar cotización')
            ->assertSee('Lo que recibes al final del', false)
            ->assertSee('noindex', false)
            ->assertDontSee('SIN VIGENCIA');
        $this->get($url)->assertOk();

        $quote->refresh();
        $this->assertSame(2, $quote->client_views);
        $this->assertNotNull($quote->client_first_viewed_at);
        $this->assertDatabaseHas('activity_logs', ['event' => 'quote_viewed', 'subject_id' => $quote->id]);
    }

    public function test_las_visitas_del_equipo_no_cuentan_y_no_puede_responder(): void
    {
        $quote = $this->issued();

        $this->get($quote->publicUrl())->assertOk()->assertSee('Vista del equipo');
        $this->assertSame(0, $quote->fresh()->client_views);
    }

    public function test_cliente_acepta_desde_el_link(): void
    {
        $quote = $this->issued();
        $url = $quote->publicUrl();
        $this->asClient();

        $this->post(route('public.quote.accept', $quote->public_token), ['name' => 'Juan Pérez', 'agree' => '1'])
            ->assertRedirect($url);

        $quote->refresh();
        $this->assertSame(QuoteStatus::Accepted, $quote->status);
        $this->assertSame('aceptada', $quote->client_response);
        $this->assertSame('Juan Pérez', $quote->client_responded_by);
        $this->assertNotNull($quote->client_response_ip);
        $this->assertSame(PipelineStage::PendingDeposit, $quote->opportunity->fresh()->stage);
        $this->assertDatabaseHas('activity_logs', ['event' => 'quote_client_response', 'subject_id' => $quote->id]);
        Mail::assertSent(QuoteClientResponded::class);

        $this->get($url)->assertSee('Cotización aceptada')->assertDontSee('data-open="m-accept"', false);

        // No se puede responder dos veces.
        $this->post(route('public.quote.reject', $quote->public_token), ['reason' => 'precio']);
        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }

    public function test_aceptar_exige_nombre_y_confirmacion(): void
    {
        $quote = $this->issued();
        $quote->publicUrl();
        $this->asClient();

        $this->post(route('public.quote.accept', $quote->public_token), ['name' => ''])->assertSessionHasErrors(['name', 'agree']);
        $this->assertSame(QuoteStatus::Issued, $quote->fresh()->status);
    }

    public function test_cliente_rechaza_con_motivo(): void
    {
        $quote = $this->issued();
        $quote->publicUrl();
        $this->asClient();

        $this->post(route('public.quote.reject', $quote->public_token), ['reason' => 'fecha', 'comment' => 'Lo vemos en primavera']);

        $quote->refresh();
        $this->assertSame(QuoteStatus::Rejected, $quote->status);
        $this->assertSame('fecha', $quote->client_response_reason);
        $this->assertSame('Lo vemos en primavera', $quote->client_response_comment);
        Mail::assertSent(QuoteClientResponded::class);
    }

    public function test_cotizacion_vencida_muestra_sin_vigencia_y_no_acepta(): void
    {
        $quote = $this->issued();
        $quote->forceFill(['valid_until' => today()->subDay()])->saveQuietly();
        $url = $quote->publicUrl();
        $this->asClient();

        $this->get($url)->assertOk()->assertSee('SIN VIGENCIA')->assertSee('Solicitar cotización actualizada')->assertDontSee('data-open="m-accept"', false);

        $this->post(route('public.quote.accept', $quote->public_token), ['name' => 'Juan Pérez', 'agree' => '1']);
        $this->assertSame(QuoteStatus::Issued, $quote->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_el_ultimo_dia_de_vigencia_todavia_se_puede_aceptar(): void
    {
        $quote = $this->issued();
        $quote->forceFill(['valid_until' => today()])->saveQuietly();
        $quote->publicUrl();
        $this->asClient();

        $this->post(route('public.quote.accept', $quote->public_token), ['name' => 'Juan Pérez', 'agree' => '1']);
        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);
    }

    public function test_borradores_y_codigos_invalidos_no_se_publican(): void
    {
        $draft = $this->service->create(Opportunity::factory()->create(), ['hectares' => 100, 'apply_vat' => false]);
        $draft->forceFill(['public_token' => str_repeat('a', 40)])->saveQuietly();
        $this->asClient();

        $this->get(route('public.quote', str_repeat('a', 40)))->assertNotFound();
        $this->get(route('public.quote', str_repeat('b', 40)))->assertNotFound();
        $this->get('/c/corto')->assertNotFound();
    }

    public function test_revision_reemplazada_lleva_a_la_version_nueva(): void
    {
        $v1 = $this->issued();
        $url = $v1->publicUrl();
        $v2 = $this->service->createRevision($v1, $this->service->inputsFrom($v1));
        $this->service->emit($v2);
        $this->asClient();

        $this->get($url)->assertOk()->assertSee('fue reemplazada')->assertSee($v2->fresh()->number);
    }

    public function test_pdf_publico_se_descarga(): void
    {
        $quote = $this->issued();
        $quote->publicUrl();
        $this->asClient();

        $response = $this->get(route('public.quote.pdf', $quote->public_token));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_enviar_link_por_whatsapp_marca_como_enviada(): void
    {
        $quote = $this->issued();

        Livewire::test(Show::class, ['quote' => $quote])
            ->assertSee('Enviar link')
            ->assertSee($quote->fresh()->public_token)
            ->call('sharedByWhatsapp');

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
        $this->assertStringContainsString(rawurlencode($quote->fresh()->publicUrl()), $quote->fresh()->whatsappShareUrl());
    }

    public function test_emitir_calcula_la_vigencia_en_dias_habiles(): void
    {
        $quote = $this->issued();

        $this->assertSame(BusinessDays::add(today(), 15)->toDateString(), $quote->valid_until->toDateString());
    }
}
