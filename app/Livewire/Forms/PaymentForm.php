<?php

namespace App\Livewire\Forms;

use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentForm extends Component
{
    public bool $showModal = false;

    #[Locked]
    public ?string $opportunityId = null;

    #[Locked]
    public ?string $paymentId = null;

    public ?string $paid_at = null;

    public ?string $amount = null;

    public ?string $payment_method_id = null;

    public string $reference = '';

    public string $notes = '';

    #[On('open-payment-form')]
    public function open(string $opportunityId, ?string $paymentId = null): void
    {
        $this->resetValidation();
        $this->reset();

        $opportunity = Opportunity::withFinancials()->findOrFail($opportunityId);
        $this->opportunityId = $opportunity->id;
        $this->paid_at = today()->toDateString();
        $this->payment_method_id = (string) PaymentMethod::active()->value('id');

        if ($paymentId) {
            $payment = Payment::where('opportunity_id', $opportunity->id)->findOrFail($paymentId);
            $this->authorize('update', $payment);
            $this->paymentId = $payment->id;
            $this->paid_at = $payment->paid_at->toDateString();
            $this->amount = (string) (float) $payment->amount;
            $this->payment_method_id = (string) $payment->payment_method_id;
            $this->reference = (string) $payment->reference;
            $this->notes = (string) $payment->notes;
        } else {
            $this->authorize('create', Payment::class);
            $balance = $opportunity->balance();
            $this->amount = $balance ? (string) $balance : null;
        }

        $this->showModal = true;
    }

    #[Computed]
    public function opportunity(): ?Opportunity
    {
        return $this->opportunityId
            ? Opportunity::withFinancials()->with(['ranch.client', 'acceptedQuote'])->find($this->opportunityId)
            : null;
    }

    protected function rules(): array
    {
        return [
            'paid_at' => ['required', 'date', 'before_or_equal:'.today()->addYear()->toDateString()],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return ['amount.gt' => 'El monto del pago debe ser mayor a $0.00.'];
    }

    public function save(PaymentService $service): void
    {
        $data = $this->validate();
        $opportunity = Opportunity::findOrFail($this->opportunityId);

        if ($this->paymentId) {
            $payment = Payment::findOrFail($this->paymentId);
            $this->authorize('update', $payment);
            $service->update($payment, $data);
            $message = 'Pago actualizado';
        } else {
            $this->authorize('create', Payment::class);
            $service->register($opportunity, $data);
            $message = 'Pago de '.money($data['amount']).' registrado';
        }

        $this->showModal = false;
        $this->dispatch('crm:refresh');
        $this->dispatch('notify', message: $message);
    }

    public function render()
    {
        return view('livewire.forms.payment-form', [
            'methods' => PaymentMethod::active()->pluck('name', 'id')->all(),
        ]);
    }
}
