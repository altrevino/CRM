<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(private OpportunityService $opportunities) {}

    /**
     * Crea una cotización nueva. Sin folio usa el consecutivo; con folio (migración desde
     * otro sistema) respeta el número capturado si está disponible.
     */
    public function create(Opportunity $opportunity, array $data): Quote
    {
        $folio = filled($data['folio'] ?? null) ? (int) $data['folio'] : null;
        $version = filled($data['version'] ?? null) ? (int) $data['version'] : 1;

        if ($folio !== null) {
            $this->assertNumberAvailable($opportunity->id, $folio, $version);
        }

        return DB::transaction(function () use ($opportunity, $data, $folio, $version) {
            $quote = new Quote($this->payload($data));
            $quote->opportunity_id = $opportunity->id;
            $quote->folio = $folio ?? Opportunity::nextQuoteFolio();
            $quote->version = $folio !== null ? $version : 1;
            $quote->status = QuoteStatus::Draft;
            $quote->save();

            return $quote;
        });
    }

    /** Crea la siguiente versión (COT-145 V2) y reemplaza las versiones abiertas anteriores. */
    public function createVersion(Quote $base, array $data): Quote
    {
        return DB::transaction(function () use ($base, $data) {
            $nextVersion = (int) Quote::withTrashed()->where('folio', $base->folio)->max('version') + 1;

            Quote::where('folio', $base->folio)
                ->where('opportunity_id', $base->opportunity_id)
                ->whereIn('status', [QuoteStatus::Draft->value, QuoteStatus::Sent->value])
                ->get()
                ->each(fn (Quote $previous) => $previous->update(['status' => QuoteStatus::Replaced]));

            $quote = new Quote($this->payload($data));
            $quote->opportunity_id = $base->opportunity_id;
            $quote->folio = $base->folio;
            $quote->version = $nextVersion;
            $quote->status = QuoteStatus::Draft;
            $quote->save();

            return $quote;
        });
    }

    public function update(Quote $quote, array $data): Quote
    {
        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages([
                'quote' => 'Solo se pueden editar cotizaciones en Borrador o Enviada. Cree una nueva versión.',
            ]);
        }

        $quote->fill($this->payload($data))->save();

        return $quote;
    }

    public function markSent(Quote $quote): void
    {
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

    private function payload(array $data): array
    {
        $applyVat = filter_var($data['apply_vat'], FILTER_VALIDATE_BOOLEAN);

        $payload = [
            'issued_at' => $data['issued_at'],
            'hectares' => $data['hectares'],
            'service_amount' => $data['service_amount'],
            'logistics_amount' => $data['logistics_amount'] ?? 0,
            'apply_vat' => $applyVat,
            'notes' => $data['notes'] ?? null,
        ];

        $payload['vat_rate'] = $applyVat ? ($data['vat_rate'] ?? Setting::vatRate()) : 0;

        return $payload;
    }
}
