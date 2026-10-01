<?php

namespace App\Services;

use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * KPIs y métricas del Dashboard. Las fórmulas están documentadas en docs/METRICAS.md.
 * Todas las cifras excluyen registros eliminados (soft delete).
 */
class MetricsService
{
    /** Ventas confirmadas: Σ total de cotizaciones aceptadas de oportunidades Confirmadas o Censo realizado. */
    public function confirmedSales(): float
    {
        return (float) DB::table('quotes')
            ->join('opportunities', 'opportunities.id', '=', 'quotes.opportunity_id')
            ->whereNull('quotes.deleted_at')
            ->whereNull('opportunities.deleted_at')
            ->where('quotes.status', QuoteStatus::Accepted->value)
            ->whereIn('opportunities.stage', PipelineStage::values(PipelineStage::won()))
            ->sum('quotes.total');
    }

    /** Número de ventas (oportunidades ganadas con cotización aceptada). */
    public function confirmedSalesCount(): int
    {
        return Opportunity::won()->whereHas('acceptedQuote')->count();
    }

    /** Hectáreas confirmadas: Σ hectáreas cotizadas de oportunidades Confirmadas o Censo realizado. */
    public function confirmedHectares(): float
    {
        return (float) Opportunity::won()->sum('quoted_hectares');
    }

    /** Censos programados a futuro: Confirmado + fecha de censo ≥ hoy. */
    public function scheduledCensusCount(): int
    {
        return Opportunity::scheduled()->whereDate('census_date', '>=', today())->count();
    }

    public function completedCensusCount(): int
    {
        return Opportunity::where('stage', PipelineStage::CensusDone->value)->count();
    }

    /** Pipeline: Σ monto cotizado de oportunidades abiertas (aceptada o, si no hay, la última cotización abierta). */
    public function pipelineAmount(): float
    {
        return (float) DB::query()
            ->fromSub(Opportunity::open()->withFinancials(), 'o')
            ->selectRaw('COALESCE(SUM(COALESCE(o.accepted_total, o.latest_quote_total, 0)), 0) as amount')
            ->value('amount');
    }

    public function openOpportunitiesCount(): int
    {
        return Opportunity::open()->count();
    }

    /** Saldo pendiente: Σ (total aceptado − pagos) de oportunidades Confirmadas o Censo realizado, por oportunidad y sin negativos. */
    public function pendingBalance(): float
    {
        return (float) DB::query()
            ->fromSub(Opportunity::won()->withFinancials(), 'o')
            ->selectRaw('COALESCE(SUM(CASE WHEN o.accepted_total > o.paid_total THEN o.accepted_total - o.paid_total ELSE 0 END), 0) as balance')
            ->value('balance');
    }

    public function overdueTasksCount(): int
    {
        return Task::overdue()->count();
    }

    /** @return array<string, float|int|null> */
    public function indicators(): array
    {
        $sales = $this->confirmedSales();
        $salesCount = $this->confirmedSalesCount();
        $hectares = $this->confirmedHectares();
        $hectaresCount = Opportunity::won()->where('quoted_hectares', '>', 0)->count();

        $won = Opportunity::won()->count();
        $lost = Opportunity::where('stage', PipelineStage::Lost->value)->count();
        $total = Opportunity::count();

        return [
            'average_ticket' => $salesCount ? $sales / $salesCount : null,
            'average_hectares' => $hectaresCount ? $hectares / $hectaresCount : null,
            'win_rate' => ($won + $lost) ? $won / ($won + $lost) * 100 : null,
            'overall_conversion' => $total ? $won / $total * 100 : null,
            'won' => $won,
            'lost' => $lost,
            'total' => $total,
            'completed_census' => $this->completedCensusCount(),
            'scheduled_census' => $this->scheduledCensusCount(),
        ];
    }

    /** @return array{labels: list<string>, values: list<int>, colors: list<string>} */
    public function opportunitiesByStage(): array
    {
        $counts = Opportunity::query()
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage');

        $stages = collect(PipelineStage::cases());

        return [
            'labels' => $stages->map->label()->all(),
            'values' => $stages->map(fn ($s) => (int) ($counts[$s->value] ?? 0))->all(),
            'colors' => $stages->map->hex()->all(),
            'keys' => $stages->map->value->all(),
        ];
    }

    /** Ventas por mes: Σ total aceptado de ventas ganadas, agrupado por mes de aceptación. @return list<float> */
    public function salesByMonth(int $year): array
    {
        $months = array_fill(1, 12, 0.0);

        DB::table('quotes')
            ->join('opportunities', 'opportunities.id', '=', 'quotes.opportunity_id')
            ->whereNull('quotes.deleted_at')
            ->whereNull('opportunities.deleted_at')
            ->where('quotes.status', QuoteStatus::Accepted->value)
            ->whereIn('opportunities.stage', PipelineStage::values(PipelineStage::won()))
            ->whereBetween('quotes.accepted_at', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
            ->get(['quotes.accepted_at', 'quotes.total'])
            ->each(function ($row) use (&$months) {
                $months[(int) substr($row->accepted_at, 5, 2)] += (float) $row->total;
            });

        return array_values($months);
    }

    /** Hectáreas confirmadas por mes de censo (Confirmado o Censo realizado). @return list<float> */
    public function hectaresByMonth(int $year): array
    {
        $months = array_fill(1, 12, 0.0);

        Opportunity::won()
            ->whereDate('census_date', '>=', "{$year}-01-01")
            ->whereDate('census_date', '<=', "{$year}-12-31")
            ->get(['census_date', 'quoted_hectares'])
            ->each(function ($row) use (&$months) {
                $months[$row->census_date->month] += (float) $row->quoted_hectares;
            });

        return array_values($months);
    }

    /** Oportunidades generadas (todas las etapas) agrupadas por estado del rancho. */
    public function opportunitiesByState(int $limit = 8): array
    {
        return Opportunity::query()
            ->join('ranches', 'ranches.id', '=', 'opportunities.ranch_id')
            ->leftJoin('mexican_states', 'mexican_states.id', '=', 'ranches.state_id')
            ->selectRaw("COALESCE(mexican_states.name, 'Sin estado') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'label')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** Oportunidades generadas (todas las etapas) agrupadas por municipio. */
    public function opportunitiesByMunicipality(int $limit = 8): array
    {
        return Opportunity::query()
            ->join('ranches', 'ranches.id', '=', 'opportunities.ranch_id')
            ->selectRaw("COALESCE(ranches.municipality, 'Sin municipio') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'label')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
