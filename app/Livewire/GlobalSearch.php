<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Quote;
use App\Models\Ranch;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** Buscador de la barra superior: clientes, teléfonos, ranchos, municipios y cotizaciones. */
class GlobalSearch extends Component
{
    public string $query = '';

    #[Computed]
    public function results(): array
    {
        $term = trim($this->query);
        if (mb_strlen($term) < 2) {
            return [];
        }

        $clients = Client::search($term)->withCount('ranches')->orderBy('name')->limit(5)->get();
        $ranches = Ranch::search($term)->with(['client', 'state'])->orderBy('name')->limit(5)->get();
        $municipalities = Ranch::query()
            ->where('municipality', 'like', "%{$term}%")
            ->selectRaw('municipality, COUNT(*) as total')
            ->groupBy('municipality')
            ->orderBy('municipality')
            ->limit(4)
            ->get();
        $quotes = Quote::query()
            ->select('quotes.*')
            ->join('opportunities', 'opportunities.id', '=', 'quotes.opportunity_id')
            ->join('ranches', 'ranches.id', '=', 'opportunities.ranch_id')
            ->join('clients', 'clients.id', '=', 'ranches.client_id')
            ->whereNull('opportunities.deleted_at')
            ->search($term)
            ->with('opportunity.ranch.client')
            ->orderByDesc('quotes.folio')
            ->orderByDesc('quotes.version')
            ->limit(5)
            ->get();

        return array_filter([
            'Clientes' => $clients->map(fn (Client $c) => [
                'url' => route('clients.show', $c),
                'title' => $c->name,
                'meta' => trim($c->formattedPhone().' · '.$c->ranches_count.' '.($c->ranches_count === 1 ? 'rancho' : 'ranchos'), ' ·'),
                'icon' => 'user',
            ])->all(),
            'Ranchos' => $ranches->map(fn (Ranch $r) => [
                'url' => route('ranches.show', $r),
                'title' => $r->name,
                'meta' => $r->client->name.' · '.$r->location(),
                'icon' => 'map',
            ])->all(),
            'Municipios' => $municipalities->map(fn ($m) => [
                'url' => route('ranches.index', ['municipio' => $m->municipality]),
                'title' => $m->municipality,
                'meta' => $m->total.' '.($m->total == 1 ? 'rancho' : 'ranchos'),
                'icon' => 'map-pin',
            ])->all(),
            'Cotizaciones' => $quotes->map(fn (Quote $q) => [
                'url' => route('opportunities.show', [$q->opportunity_id, 'tab' => 'cotizaciones']),
                'title' => $q->number.' · '.money($q->total),
                'meta' => $q->opportunity->ranch->client->name.' · '.$q->opportunity->ranch->name.' · '.$q->status->label(),
                'icon' => 'document',
            ])->all(),
        ]);
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
