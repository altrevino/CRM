<?php

namespace App\Services;

use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/** Detecta registros que requieren acción ("Requieren atención" del Dashboard). */
class AttentionService
{
    /**
     * Cotizaciones Enviadas hace N días o más (configurable), con la oportunidad abierta y sin
     * actividad posterior al envío: sin comentarios, tareas creadas/completadas, pagos ni contacto.
     */
    public function staleQuotesQuery(): Builder
    {
        $days = Setting::quoteFollowupDays();

        return Quote::query()
            ->select('quotes.*')
            ->join('opportunities', 'opportunities.id', '=', 'quotes.opportunity_id')
            ->whereNull('opportunities.deleted_at')
            ->whereIn('opportunities.stage', PipelineStage::values(PipelineStage::open()))
            ->where('quotes.status', QuoteStatus::Sent->value)
            ->where('quotes.sent_at', '<=', now()->subDays($days))
            ->whereNotExists(fn (QueryBuilder $q) => $q->from('comments')
                ->whereColumn('comments.opportunity_id', 'quotes.opportunity_id')
                ->whereColumn('comments.created_at', '>', 'quotes.sent_at'))
            ->whereNotExists(fn (QueryBuilder $q) => $q->from('tasks')
                ->whereColumn('tasks.opportunity_id', 'quotes.opportunity_id')
                ->where(fn (QueryBuilder $w) => $w->whereColumn('tasks.created_at', '>', 'quotes.sent_at')
                    ->orWhereColumn('tasks.completed_at', '>', 'quotes.sent_at')
                    ->orWhere('tasks.status', 'pendiente')))
            ->whereNotExists(fn (QueryBuilder $q) => $q->from('payments')
                ->whereNull('payments.deleted_at')
                ->whereColumn('payments.opportunity_id', 'quotes.opportunity_id')
                ->whereColumn('payments.created_at', '>', 'quotes.sent_at'))
            ->where(fn (Builder $q) => $q->whereNull('opportunities.last_contact_at')
                ->orWhereColumn('opportunities.last_contact_at', '<', 'quotes.sent_at'));
    }

    /** @return list<string> IDs de cotizaciones que requieren seguimiento */
    public function staleQuoteIds(): array
    {
        return $this->staleQuotesQuery()->pluck('quotes.id')->all();
    }

    /**
     * @return Collection<int, array{key: string, label: string, tone: string, items: Collection}>
     */
    public function sections(int $limit = 5): Collection
    {
        $upcomingDays = Setting::upcomingCensusDays();
        $with = ['ranch.client', 'ranch.state'];

        $overdue = Task::overdue()->with(['opportunity.ranch.client', 'client'])->orderBy('due_date')->limit($limit)->get()
            ->map(fn (Task $t) => [
                'title' => $t->title,
                'subtitle' => collect([$t->client?->name, $t->opportunity?->ranch?->name])->filter()->implode(' · '),
                'meta' => 'Venció '.mb_strtolower(fecha_relativa($t->due_date)),
                'url' => $t->opportunity_id ? route('opportunities.show', [$t->opportunity_id, 'tab' => 'seguimientos']) : ($t->client_id ? route('clients.show', $t->client_id) : route('tasks.index')),
                'action' => 'Atender',
            ]);

        $staleQuotes = $this->staleQuotesQuery()->with('opportunity.ranch.client')->orderBy('quotes.sent_at')->limit($limit)->get()
            ->map(fn (Quote $q) => [
                'title' => "{$q->number} · ".money($q->total),
                'subtitle' => $q->opportunity->ranch->client->name.' · '.$q->opportunity->ranch->name,
                'meta' => 'Enviada hace '.(int) $q->sent_at->copy()->startOfDay()->diffInDays(today()).' días sin actividad',
                'url' => route('opportunities.show', [$q->opportunity_id, 'tab' => 'cotizaciones']),
                'action' => 'Dar seguimiento',
            ]);

        $upcomingWithBalance = Opportunity::scheduled()->withFinancials()->with($with)
            ->whereDate('census_date', '>=', today())
            ->whereDate('census_date', '<=', today()->addDays($upcomingDays))
            ->orderBy('census_date')
            ->get()
            ->filter(fn (Opportunity $o) => $o->acceptedTotal() === null || $o->balance() > 0)
            ->take($limit)
            ->map(fn (Opportunity $o) => [
                'title' => $o->ranch->name.' · '.fecha($o->census_date),
                'subtitle' => $o->ranch->client->name,
                'meta' => $o->acceptedTotal() === null ? 'Sin cotización aceptada' : 'Saldo '.money($o->balance()),
                'url' => route('opportunities.show', [$o->id, 'tab' => 'pagos']),
                'action' => 'Ver pagos',
            ]);

        $followUpWithoutTask = Opportunity::where('stage', PipelineStage::FollowUp->value)
            ->whereDoesntHave('pendingTasks')
            ->with($with)->orderBy('stage_changed_at')->limit($limit)->get()
            ->map(fn (Opportunity $o) => [
                'title' => $o->ranch->name,
                'subtitle' => $o->ranch->client->name,
                'meta' => 'En seguimiento sin próxima tarea',
                'url' => route('opportunities.show', [$o->id, 'tab' => 'seguimientos']),
                'action' => 'Crear tarea',
            ]);

        $depositWithoutPayment = Opportunity::where('stage', PipelineStage::PendingDeposit->value)
            ->whereDoesntHave('payments')
            ->with($with)->orderBy('stage_changed_at')->limit($limit)->get()
            ->map(fn (Opportunity $o) => [
                'title' => $o->ranch->name,
                'subtitle' => $o->ranch->client->name,
                'meta' => 'Pendiente anticipo desde '.fecha($o->stage_changed_at),
                'url' => route('opportunities.show', [$o->id, 'tab' => 'pagos']),
                'action' => 'Registrar pago',
            ]);

        $pastCensus = Opportunity::scheduled()->whereDate('census_date', '<', today())
            ->with($with)->orderBy('census_date')->limit($limit)->get()
            ->map(fn (Opportunity $o) => [
                'title' => $o->ranch->name.' · '.fecha($o->census_date),
                'subtitle' => $o->ranch->client->name,
                'meta' => 'La fecha ya pasó: ¿se realizó el censo?',
                'url' => route('opportunities.show', $o->id),
                'action' => 'Actualizar etapa',
            ]);

        return collect([
            ['key' => 'overdue', 'label' => 'Seguimientos vencidos', 'tone' => 'rose', 'items' => $overdue, 'count' => Task::overdue()->count()],
            ['key' => 'stale_quotes', 'label' => 'Cotizaciones enviadas sin seguimiento', 'tone' => 'amber', 'items' => $staleQuotes, 'count' => $this->staleQuotesQuery()->count()],
            ['key' => 'census_balance', 'label' => "Censos próximos ({$upcomingDays} días) con saldo pendiente", 'tone' => 'blue', 'items' => $upcomingWithBalance, 'count' => $upcomingWithBalance->count()],
            ['key' => 'followup_no_task', 'label' => 'En Seguimiento sin próxima tarea', 'tone' => 'amber', 'items' => $followUpWithoutTask, 'count' => Opportunity::where('stage', PipelineStage::FollowUp->value)->whereDoesntHave('pendingTasks')->count()],
            ['key' => 'deposit_no_payment', 'label' => 'Pendiente anticipo sin pago', 'tone' => 'blue', 'items' => $depositWithoutPayment, 'count' => Opportunity::where('stage', PipelineStage::PendingDeposit->value)->whereDoesntHave('payments')->count()],
            ['key' => 'past_census', 'label' => 'Censos con fecha pasada sin marcar como realizados', 'tone' => 'slate', 'items' => $pastCensus, 'count' => Opportunity::scheduled()->whereDate('census_date', '<', today())->count()],
        ])->filter(fn ($section) => $section['count'] > 0)->values();
    }
}
