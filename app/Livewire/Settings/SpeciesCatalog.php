<?php

namespace App\Livewire\Settings;

use App\Models\Species;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Catálogo de especies')]
class SpeciesCatalog extends Component
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
        $data = $this->validate(['name' => ['required', 'string', 'max:100', Rule::unique('species', 'name')]], [], ['name' => 'especie']);
        Species::create(['name' => trim($data['name']), 'is_active' => true]);
        $this->reset('name');
        $this->dispatch('notify', message: 'Especie agregada');
    }

    public function edit(int $id): void
    {
        $species = Species::findOrFail($id);
        $this->editingId = $species->id;
        $this->editingName = $species->name;
    }

    public function update(): void
    {
        $this->authorize('manage-settings');
        $data = $this->validate(['editingName' => ['required', 'string', 'max:100', Rule::unique('species', 'name')->ignore($this->editingId)]], [], ['editingName' => 'especie']);
        Species::findOrFail($this->editingId)->update(['name' => trim($data['editingName'])]);
        $this->reset('editingId', 'editingName');
    }

    public function toggle(int $id): void
    {
        $this->authorize('manage-settings');
        $species = Species::findOrFail($id);
        $species->update(['is_active' => ! $species->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-settings');
        $species = Species::withCount('opportunities')->findOrFail($id);
        if ($species->opportunities_count > 0) {
            $this->dispatch('notify', message: 'Está en uso por servicios; desactívala en lugar de eliminarla.', type: 'error');

            return;
        }
        $species->delete();
    }

    public function render()
    {
        return view('livewire.settings.species', [
            'species' => Species::withCount('opportunities')->orderBy('name')->get(),
        ]);
    }
}
