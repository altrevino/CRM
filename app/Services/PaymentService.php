<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /** Registra un pago. Se liga a la cotización aceptada vigente cuando existe. */
    public function register(Opportunity $opportunity, array $data): Payment
    {
        return DB::transaction(function () use ($opportunity, $data) {
            $payment = new Payment([
                'paid_at' => $data['paid_at'],
                'amount' => $data['amount'],
                'payment_method_id' => $data['payment_method_id'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $payment->opportunity_id = $opportunity->id;
            $payment->quote_id = $opportunity->acceptedQuote()->value('id');
            $payment->save();

            return $payment;
        });
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->update([
            'paid_at' => $data['paid_at'],
            'amount' => $data['amount'],
            'payment_method_id' => $data['payment_method_id'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $payment;
    }

    /** Soft delete: el pago queda en la base y la eliminación en el historial. */
    public function delete(Payment $payment): void
    {
        $payment->delete();
    }
}
