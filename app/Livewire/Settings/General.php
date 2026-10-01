<?php

namespace App\Livewire\Settings;

use App\Enums\ActivityEvent;
use App\Models\MexicanState;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Configuración')]
class General extends Component
{
    public string $vat_rate = '';

    public string $quote_followup_days = '';

    public string $upcoming_census_days = '';

    public string $quote_folio_start = '';

    public function mount(): void
    {
        $this->authorize('manage-settings');
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $this->{$key} = (string) Setting::get($key);
        }
    }

    protected function rules(): array
    {
        return [
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'quote_followup_days' => ['required', 'integer', 'min:1', 'max:90'],
            'upcoming_census_days' => ['required', 'integer', 'min:1', 'max:90'],
            'quote_folio_start' => ['required', 'integer', 'min:1', 'max:9999999'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'vat_rate' => 'IVA predeterminado',
            'quote_followup_days' => 'días sin seguimiento',
            'upcoming_census_days' => 'días de censo próximo',
            'quote_folio_start' => 'folio inicial',
        ];
    }

    public function save(): void
    {
        $this->authorize('manage-settings');
        $data = $this->validate();

        $changes = [];
        foreach ($data as $key => $value) {
            if ((string) Setting::get($key) !== (string) $value) {
                $changes[] = $key.': '.Setting::get($key).' → '.$value;
                Setting::set($key, $value);
            }
        }

        if ($changes) {
            ActivityLogger::log(ActivityEvent::SettingsUpdated, null, implode('; ', $changes));
        }

        $this->dispatch('notify', message: 'Configuración guardada');
    }

    public function render()
    {
        return view('livewire.settings.general', [
            'states' => MexicanState::orderBy('name')->get(),
        ]);
    }
}
