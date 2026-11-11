<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\ActivityLogger;

class QuoteObserver
{
    public function created(Quote $quote): void
    {
        ActivityLogger::log(
            ActivityEvent::QuoteCreated,
            $quote,
            "{$quote->number} creada · ".money($quote->total)." ({$quote->vatLabel()})",
            ['total' => (string) $quote->total]
        );
    }

    public function updated(Quote $quote): void
    {
        if ($quote->wasChanged('status')) {
            $event = match ($quote->status) {
                QuoteStatus::Issued => ActivityEvent::QuoteIssued,
                QuoteStatus::Sent => ActivityEvent::QuoteSent,
                QuoteStatus::Accepted => ActivityEvent::QuoteAccepted,
                QuoteStatus::Rejected => ActivityEvent::QuoteRejected,
                QuoteStatus::Replaced => ActivityEvent::QuoteReplaced,
                QuoteStatus::Draft => ActivityEvent::QuoteUpdated,
            };
            $text = match ($quote->status) {
                QuoteStatus::Issued => "{$quote->number} emitida · ".money($quote->total).($quote->pricing_snapshot ? ' · Tarifa v'.$quote->pricing_snapshot['version'] : ''),
                QuoteStatus::Sent => "{$quote->number} enviada al cliente",
                QuoteStatus::Accepted => "{$quote->number} aceptada · ".money($quote->total),
                QuoteStatus::Rejected => "{$quote->number} rechazada",
                QuoteStatus::Replaced => "{$quote->number} reemplazada",
                QuoteStatus::Draft => "{$quote->number} regresó a borrador",
            };
            ActivityLogger::log($event, $quote, $text);
        }

        $amounts = ['hectares', 'service_amount', 'logistics_amount', 'travel_expenses', 'discount_amount', 'apply_vat', 'vat_rate', 'issued_at', 'notes', 'price_list_id'];
        if ($quote->wasChanged($amounts)) {
            $text = "{$quote->number} editada";
            if ($quote->wasChanged('total')) {
                $text .= ': total '.money($quote->getOriginal('total')).' → '.money($quote->total);
            }
            ActivityLogger::log(ActivityEvent::QuoteUpdated, $quote, $text);
        }
    }

    public function deleted(Quote $quote): void
    {
        ActivityLogger::log(
            ActivityEvent::QuoteDeleted,
            $quote,
            "{$quote->number} eliminada (total ".money($quote->total).')',
            ['total' => (string) $quote->total]
        );
    }
}
