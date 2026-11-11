<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Models\Opportunity;
use App\Models\PriceList;
use App\Models\Quote;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\QuoteCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(private OpportunityService $opportunities) {}

    /**
     * Crea un borrador con la tarifa vigente. Sin folio usa el consecutivo; con folio
     * (migración desde otro sistema) respeta el número capturado si está disponible.
     *
     * @param  array  $data  Captura de cotización (ver QuoteCalculator::calculate) + issued_at, valid_until,
     *                       tentative_service_date, payment_terms, observations, notes, folio, version.
     */
    public function create(Opportunity $opportunity, array $data, ?string $duplicatedFrom = null): Quote
    {
        $folio = filled($data['folio'] ?? null) ? (int) $data['folio'] : null;
        $version = filled($data['version'] ?? null) ? (int) $data['version'] : 1;

        if ($folio !== null) {
            $this->assertNumberAvailable($opportunity->id, $folio, $version);
        }

        return DB::transaction(function () use ($opportunity, $data, $folio, $version, $duplicatedFrom) {
            $quote = new Quote;
            $quote->opportunity_id = $opportunity->id;
            $quote->folio = $folio ?? Opportunity::nextQuoteFolio();
            $quote->version = $folio !== null ? $version : 1;
            $quote->status = QuoteStatus::Draft;
            $quote->duplicated_from = $duplicatedFrom;
            $this->apply($quote, PriceList::current(), $data);
            $quote->save();

            return $quote;
        });
    }

    /**
     * Revisión: nueva versión del mismo folio (COT-145 V2) con la tarifa vigente.
     * La revisión anterior se conserva; si estaba en borrador, emitida o enviada pasa a Reemplazada.
     */
    public function createRevision(Quote $base, array $data): Quote
    {
        return DB::transaction(function () use ($base, $data) {
            $nextVersion = (int) Quote::withTrashed()->where('folio', $base->folio)->max('version') + 1;

            Quote::where('folio', $base->folio)
                ->where('opportunity_id', $base->opportunity_id)
                ->whereIn('status', [QuoteStatus::Draft->value, QuoteStatus::Issued->value, QuoteStatus::Sent->value])
                ->get()
                ->each(fn (Quote $previous) => $previous->update(['status' => QuoteStatus::Replaced]));

            $quote = new Quote;
            $quote->opportunity_id = $base->opportunity_id;
            $quote->folio = $base->folio;
            $quote->version = $nextVersion;
            $quote->status = QuoteStatus::Draft;
            $quote->revision_of = $base->id;
            $this->apply($quote, PriceList::current(), $data);
            $quote->save();

            return $quote;
        });
    }

    /**
     * Edita un borrador. Conserva la tarifa con la que se creó, salvo que se pida
     * actualizarla o que sea una cotización previa al tarifario.
     */
    public function updateDraft(Quote $quote, array $data, bool $useCurrentPricing = false): Quote
    {
        $this->assertDraft($quote);

        $priceList = ($useCurrentPricing || ! $quote->pricing_snapshot) ? PriceList::current() : null;
        $this->apply($quote, $priceList, $data);
        $quote->save();

        return $quote;
    }

    /** Recalcula un borrador con la tarifa vigente conservando lo capturado. */
    public function refreshPricing(Quote $quote): Quote
    {
        return $this->updateDraft($quote, $this->inputsFrom($quote), useCurrentPricing: true);
    }

    /**
     * Emite el borrador. Si usa una tarifa que ya no es la vigente exige un motivo
     * explícito; de lo contrario hay que actualizarla antes.
     */
    public function emit(Quote $quote, ?string $overrideReason = null): void
    {
        $this->assertDraft($quote);

        $overrideReason = trim((string) $overrideReason);
        if (! $quote->usesCurrentPricing() && mb_strlen($overrideReason) < 5) {
            throw ValidationException::withMessages([
                'pricing' => 'Esta cotización usa '.$quote->pricingLabel().' y la vigente es '.PriceList::current()->label().'. Actualízala a la tarifa vigente o confirma el uso de la anterior indicando el motivo.',
            ]);
        }

        DB::transaction(function () use ($quote, $overrideReason) {
            $quote->status = QuoteStatus::Issued;
            $quote->emitted_at = now();
            $quote->issued_at = today();
            if (! $quote->valid_until || $quote->valid_until->lt(today())) {
                $quote->valid_until = today()->addDays((int) ($quote->pricing_snapshot['validity_days'] ?? PriceList::current()->validity_days));
            }
            if (! $quote->usesCurrentPricing()) {
                $quote->pricing_override_reason = $overrideReason;
            }
            $quote->save();

            if (! $quote->usesCurrentPricing()) {
                ActivityLogger::log(ActivityEvent::QuoteUpdated, $quote, "{$quote->number} emitida con {$quote->pricingLabel()} (no vigente). Motivo: {$overrideReason}");
            }
        });
    }

    /**
     * Datos capturados de una cotización, para editarla, revisarla, duplicarla o actualizar su tarifa.
     * Al duplicar ($withExceptions = false) no se copian descuentos ni ajustes excepcionales.
     */
    public function inputsFrom(Quote $quote, bool $withExceptions = true): array
    {
        $inputs = [
            'hectares' => (string) (float) $quote->hectares,
            'one_way_km' => $quote->one_way_km !== null ? (string) (float) $quote->one_way_km : '0',
            'travel_expenses' => (string) (float) $quote->travel_expenses,
            'apply_vat' => (bool) $quote->apply_vat,
            'tentative_service_date' => $quote->tentative_service_date?->toDateString(),
            'payment_terms' => $quote->payment_terms,
            'observations' => $quote->observations,
            'notes' => $quote->notes,
            'route_adjusted' => false,
            'service_adjusted' => false,
        ];

        if ($withExceptions) {
            $inputs += [
                'issued_at' => $quote->issued_at?->toDateString(),
                'valid_until' => $quote->valid_until?->toDateString(),
            ];
            if ($quote->route_adjust_reason) {
                $inputs['route_adjusted'] = true;
                $inputs['route_km'] = (string) (float) $quote->route_km;
                $inputs['route_adjust_reason'] = $quote->route_adjust_reason;
            }
            if ($quote->service_adjust_reason) {
                $inputs['service_adjusted'] = true;
                $inputs['service_amount'] = (string) (float) $quote->service_amount;
                $inputs['service_adjust_reason'] = $quote->service_adjust_reason;
            }
            if ($quote->discount_type) {
                $inputs['discount_type'] = $quote->discount_type;
                $inputs['discount_value'] = (string) (float) $quote->discount_value;
                $inputs['discount_scope'] = $quote->discount_scope;
                $inputs['discount_reason'] = $quote->discount_reason;
            }
        }

        return $inputs;
    }

    /** Calcula con la tarifa indicada (o la ya guardada en el borrador) y llena la cotización. */
    private function apply(Quote $quote, ?PriceList $priceList, array $data): void
    {
        $snapshot = $priceList ? $priceList->snapshot() : $quote->pricing_snapshot;

        try {
            $result = QuoteCalculator::calculate($snapshot, $data);
        } catch (PricingException $e) {
            throw ValidationException::withMessages([$e->field => $e->getMessage()]);
        }

        $wasAdjusted = $quote->service_adjust_reason !== null;
        $adjustmentChanged = $result['service_adjusted'] && (! $wasAdjusted
            || (string) $quote->service_amount !== $result['service_amount']
            || $quote->service_adjust_reason !== trim($data['service_adjust_reason']));

        if ($priceList) {
            $quote->price_list_id = $priceList->id;
            $quote->pricing_snapshot = $snapshot;
        }

        $issuedAt = Carbon::parse(($data['issued_at'] ?? null) ?: ($quote->issued_at ?? today()))->startOfDay();

        $quote->fill([
            'hectares' => $result['hectares'],
            'service_amount_calculated' => $result['service_amount_calculated'],
            'service_amount' => $result['service_amount'],
            'service_adjust_reason' => $result['service_adjusted'] ? trim($data['service_adjust_reason']) : null,
            'one_way_km' => $result['one_way_km'],
            'route_km_calculated' => $result['route_km_calculated'],
            'route_km' => $result['route_km'],
            'route_adjust_reason' => $result['route_adjusted'] ? trim($data['route_adjust_reason']) : null,
            'km_rate' => $result['km_rate'],
            'logistics_amount' => $result['logistics_amount'],
            'travel_expenses' => $result['travel_expenses'],
            'discount_type' => $result['discount_type'],
            'discount_value' => $result['discount_value'],
            'discount_scope' => $result['discount_scope'],
            'discount_base' => $result['discount_base'],
            'discount_amount' => $result['discount_amount'],
            'discount_reason' => $result['discount_type'] ? trim($data['discount_reason']) : null,
            'apply_vat' => $result['apply_vat'],
            'vat_rate' => $result['vat_rate'],
            'issued_at' => $issuedAt,
            'valid_until' => ($data['valid_until'] ?? null) ?: ($quote->valid_until ?? $issuedAt->copy()->addDays((int) $snapshot['validity_days'])),
            'tentative_service_date' => ($data['tentative_service_date'] ?? null) ?: null,
            'payment_terms' => array_key_exists('payment_terms', $data) ? ($data['payment_terms'] ?: null) : ($quote->payment_terms ?? $snapshot['payment_terms']),
            'observations' => ($data['observations'] ?? null) ?: null,
            'notes' => ($data['notes'] ?? null) ?: null,
        ]);

        if (! $result['service_adjusted']) {
            $quote->service_adjusted_by = null;
            $quote->service_adjusted_at = null;
        } elseif ($adjustmentChanged) {
            $quote->service_adjusted_by = Auth::id();
            $quote->service_adjusted_at = now();
        }
    }

    private function assertDraft(Quote $quote): void
    {
        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages([
                'quote' => 'Solo se editan borradores. Para cambiar una cotización emitida crea una revisión.',
            ]);
        }
    }

    public function markSent(Quote $quote): void
    {
        if ($quote->status === QuoteStatus::Draft) {
            $this->emit($quote);
        }

        DB::transaction(function () use ($quote) {
            $quote->status = QuoteStatus::Sent;
            $quote->sent_at = now();
            $quote->save();

            $this->opportunities->advanceTo($quote->opportunity, PipelineStage::QuoteSent);
        });
    }

    /** Marca la cotización como la aceptada/vigente. Cualquier otra aceptada pasa a Reemplazada. */
    public function accept(Quote $quote): void
    {
        if ($quote->status === QuoteStatus::Draft) {
            $this->emit($quote);
        }

        DB::transaction(function () use ($quote) {
            // Defensa: ninguna cotización eliminada o no aceptada debe conservar el candado.
            Quote::withTrashed()
                ->where('opportunity_id', $quote->opportunity_id)
                ->whereNotNull('accepted_lock')
                ->where(fn ($q) => $q->whereNotNull('deleted_at')->orWhere('status', '!=', QuoteStatus::Accepted->value))
                ->update(['accepted_lock' => null]);

            Quote::where('opportunity_id', $quote->opportunity_id)
                ->where('status', QuoteStatus::Accepted->value)
                ->whereKeyNot($quote->id)
                ->lockForUpdate()
                ->get()
                ->each(fn (Quote $other) => $other->update(['status' => QuoteStatus::Replaced]));

            $quote->status = QuoteStatus::Accepted;
            $quote->accepted_at = now();
            $quote->sent_at ??= now();
            $quote->save();

            $opportunity = $quote->opportunity;
            if ((float) $opportunity->quoted_hectares !== (float) $quote->hectares) {
                $opportunity->quoted_hectares = $quote->hectares;
                $opportunity->save();
            }

            $this->opportunities->advanceTo($opportunity, PipelineStage::PendingDeposit);
        });
    }

    /**
     * Ajuste de datos históricos (migración): número y fechas de envío/aceptación.
     * Si cambia el folio, todas las versiones de esa cotización lo adoptan.
     *
     * @param  array{folio: int|string, version: int|string, sent_at?: ?string, accepted_at?: ?string}  $data
     */
    public function adjust(Quote $quote, array $data): void
    {
        $folio = (int) $data['folio'];
        $version = (int) $data['version'];
        $oldNumber = $quote->number;

        $family = $folio !== $quote->folio
            ? Quote::withTrashed()->where('opportunity_id', $quote->opportunity_id)->where('folio', $quote->folio)->get()
            : collect([$quote]);
        $ids = $family->pluck('id')->all();

        foreach ($family as $member) {
            $this->assertNumberAvailable($quote->opportunity_id, $folio, $member->is($quote) ? $version : $member->version, $ids);
        }
        if ($family->contains(fn (Quote $m) => ! $m->is($quote) && $m->version === $version)) {
            throw ValidationException::withMessages(['version' => "Ya existe la versión {$version} de esta cotización."]);
        }

        DB::transaction(function () use ($quote, $family, $folio, $version, $data, $oldNumber) {
            foreach ($family as $member) {
                $member->folio = $folio;
                if ($member->is($quote)) {
                    $member->version = $version;
                }
                $member->save();
            }

            $changes = [];
            if ($quote->number !== $oldNumber) {
                $changes[] = "número {$oldNumber} → {$quote->number}".($family->count() > 1 ? ' (y sus otras versiones)' : '');
            }

            foreach (['sent_at' => 'envío', 'accepted_at' => 'aceptación'] as $field => $label) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }
                $new = filled($data[$field]) ? Carbon::parse($data[$field])->setTime(12, 0) : null;
                if ($quote->{$field}?->toDateString() !== $new?->toDateString()) {
                    $changes[] = "fecha de {$label} ".fecha($quote->{$field}).' → '.fecha($new);
                    $quote->{$field} = $new;
                }
            }
            $quote->save();

            if ($changes) {
                ActivityLogger::log(ActivityEvent::QuoteUpdated, $quote, "{$quote->number} ajustada: ".implode('; ', $changes));
            }
        });
    }

    /** Valida que COT-folio[ Vn] esté libre y que el folio no pertenezca a otro servicio. */
    public function assertNumberAvailable(string $opportunityId, int $folio, int $version, array $ignoreIds = []): void
    {
        $number = Quote::formatNumber($folio, $version);

        $taken = Quote::withTrashed()->where('number', $number)->whereNotIn('id', $ignoreIds)->first();
        if ($taken) {
            throw ValidationException::withMessages([
                'folio' => "{$number} ya existe".($taken->trashed() ? ' en una cotización eliminada' : '').'.',
            ]);
        }

        $otherService = Quote::withTrashed()->where('folio', $folio)
            ->where('opportunity_id', '!=', $opportunityId)
            ->whereNotIn('id', $ignoreIds)
            ->exists();
        if ($otherService) {
            throw ValidationException::withMessages(['folio' => "El folio COT-{$folio} ya pertenece a otro servicio."]);
        }
    }

    public function reject(Quote $quote): void
    {
        $quote->update(['status' => QuoteStatus::Rejected]);
    }

    public function delete(Quote $quote): void
    {
        if ($quote->status === QuoteStatus::Accepted && $quote->payments()->exists()) {
            throw ValidationException::withMessages([
                'quote' => 'No se puede eliminar la cotización aceptada porque tiene pagos registrados.',
            ]);
        }

        DB::transaction(function () use ($quote) {
            // Libera el candado de "aceptada" antes del soft delete. saveQuietly() no dispara
            // el evento que calcula accepted_lock, por eso se limpia explícitamente.
            if ($quote->status === QuoteStatus::Accepted) {
                $quote->forceFill(['status' => QuoteStatus::Replaced, 'accepted_lock' => null])->saveQuietly();
            }
            $quote->delete();
        });
    }
}
