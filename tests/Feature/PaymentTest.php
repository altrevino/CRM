<?php

namespace Tests\Feature;

use App\Livewire\Forms\PaymentForm;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use App\Services\QuoteService;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    private Opportunity $opportunity;

    private PaymentService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
        $this->payments = app(PaymentService::class);
        $this->opportunity = Opportunity::factory()->create();

        $quotes = app(QuoteService::class);
        $quote = $quotes->create($this->opportunity, [
            'issued_at' => today(), 'hectares' => 1000, 'service_amount' => 80000, 'logistics_amount' => 0, 'apply_vat' => false,
        ]);
        $quotes->accept($quote);
    }

    public function test_saldo_se_calcula_con_pagos_parciales(): void
    {
        $this->pay(40000);
        $this->pay(20000);

        $opportunity = Opportunity::withFinancials()->find($this->opportunity->id);

        $this->assertEquals(80000, $opportunity->acceptedTotal());
        $this->assertEquals(60000, $opportunity->paidTotal());
        $this->assertEquals(20000, $opportunity->balance());
        $this->assertSame(75, $opportunity->paidPercent());
    }

    public function test_el_pago_se_liga_a_la_cotizacion_aceptada(): void
    {
        $payment = $this->pay(10000);

        $this->assertSame($this->opportunity->acceptedQuote->id, $payment->quote_id);
    }

    public function test_pago_eliminado_no_cuenta_en_el_saldo_y_queda_registrado(): void
    {
        $payment = $this->pay(30000);
        $this->payments->delete($payment);

        $opportunity = Opportunity::withFinancials()->find($this->opportunity->id);
        $this->assertEquals(80000, $opportunity->balance());
        $this->assertSoftDeleted($payment);
        $this->assertDatabaseHas('activity_logs', ['event' => 'payment_deleted', 'subject_id' => $payment->id]);
    }

    public function test_no_permite_pagos_negativos_ni_en_cero(): void
    {
        foreach (['-100', '0'] as $amount) {
            Livewire::test(PaymentForm::class)
                ->call('open', $this->opportunity->id)
                ->set('amount', $amount)
                ->call('save')
                ->assertHasErrors(['amount']);
        }

        $this->assertSame(0, Payment::count());
    }

    public function test_formulario_registra_pago_y_lo_atribuye_al_usuario(): void
    {
        Livewire::test(PaymentForm::class)
            ->call('open', $this->opportunity->id)
            ->set('amount', '25000')
            ->call('save')
            ->assertHasNoErrors();

        $payment = Payment::first();
        $this->assertEquals(25000, (float) $payment->amount);
        $this->assertSame(auth()->id(), $payment->created_by);
        $this->assertDatabaseHas('activity_logs', ['event' => 'payment_registered', 'user_id' => auth()->id()]);
    }

    private function pay(float $amount): Payment
    {
        return $this->payments->register($this->opportunity, [
            'paid_at' => today(), 'amount' => $amount, 'payment_method_id' => PaymentMethod::first()->id,
        ]);
    }
}
