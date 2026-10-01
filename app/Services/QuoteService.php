<?php

namespace App\Services;

use App\Enums\PipelineStage;
use App\Enums\QuoteStatus;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(private OpportunityService $opportunities) {}

    /** Crea una cotización nueva (folio nuevo, versión 1). */
    public function create(Opportunity $opportunity, array $data): Quote
    {
        return DB::transaction(function () use ($opportunity, $data) {
            $quote = new Quote($this->payload($data));
            $quote->opportunity_id = $opportunity->id;
            $quote->folio = Opportunity::nextQuoteFolio();
            $quote->version = 1;
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
            // Libera el candado de "aceptada" antes del soft delete.
            if ($quote->status === QuoteStatus::Accepted) {
                $quote->status = QuoteStatus::Replaced;
                $quote->saveQuietly();
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
