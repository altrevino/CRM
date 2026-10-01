<?php

namespace App\Livewire\Settings;

use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Formas de pago')]
class PaymentMethods extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public string $editingName = '';

    public function mount(): void
    {
        $this->authorize('manage-settings');
    }

    public function add(): void
    {
        $this->authorize('manage-settings');
        $data = $this->validate(['name' => ['required', 'string', 'max:60', Rule::unique('payment_methods', 'name')]], [], ['name' => 'forma de pago']);
        PaymentMethod::create(['name' => trim($data['name']), 'is_active' => true, 'sort_order' => (int) PaymentMethod::max('sort_order') + 1]);
        $this->reset('name');
        $this->dispatch('notify', message: 'Forma de pago agregada');
    }

    public function edit(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);
        $this->editingId = $method->id;
        $this->editingName = $method->name;
    }

    public function update(): void
    {
        $this->authorize('manage-settings');
        $data = $this->validate(['editingName' => ['required', 'string', 'max:60', Rule::unique('payment_methods', 'name')->ignore($this->editingId)]], [], ['editingName' => 'forma de pago']);
        PaymentMethod::findOrFail($this->editingId)->update(['name' => trim($data['editingName'])]);
        $this->reset('editingId', 'editingName');
    }

    public function toggle(int $id): void
    {
        $this->authorize('manage-settings');
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => ! $method->is_active]);
    }

    public function render()
    {
        return view('livewire.settings.payment-methods', [
            'methods' => PaymentMethod::withCount('payments')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
