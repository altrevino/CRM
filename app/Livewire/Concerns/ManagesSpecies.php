<?php

namespace App\Livewire\Concerns;

use App\Models\Opportunity;
use App\Models\Species;
use Illuminate\Support\Str;

/** Alta rápida de especies desde el multi-select (sin tocar código). */
trait ManagesSpecies
{
    public function addSpecies(string $name): ?array
    {
        $this->authorize('create', Opportunity::class);

        $name = Str::of($name)->squish()->limit(100, '')->ucfirst()->toString();
        if (mb_strlen($name) < 2) {
            return null;
        }

        $species = Species::firstOrCreate(['name' => $name], ['is_active' => true]);

        return ['id' => (string) $species->id, 'name' => $species->name];
    }

    protected function speciesOptions(): array
    {
        return Species::active()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($s) => ['id' => (string) $s->id, 'name' => $s->name])
            ->all();
    }
}
