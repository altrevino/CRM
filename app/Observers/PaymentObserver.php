<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Models\Payment;
use App\Services\ActivityLogger;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $text = 'Pago de '.money($payment->amount).' · '.$payment->method?->name;
        if ($payment->reference) {
            $text .= " · Ref. {$payment->reference}";
        }

        ActivityLogger::log(ActivityEvent::PaymentRegistered, $payment, $text, ['amount' => (string) $payment->amount]);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged(['amount', 'paid_at', 'payment_method_id', 'reference', 'notes'])) {
            $text = 'Pago editado';
            if ($payment->wasChanged('amount')) {
                $text .= ': '.money($payment->getOriginal('amount')).' → '.money($payment->amount);
            }
            ActivityLogger::log(ActivityEvent::PaymentUpdated, $payment, $text);
        }
    }

    public function deleted(Payment $payment): void
    {
        ActivityLogger::log(
            ActivityEvent::PaymentDeleted,
            $payment,
            'Pago de '.money($payment->amount).' del '.fecha($payment->paid_at).' eliminado',
            ['amount' => (string) $payment->amount]
        );
    }
}
