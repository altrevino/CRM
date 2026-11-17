<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\QuotePdf;
use App\Services\QuoteService;
use App\Support\BusinessDays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Página pública de la cotización (link para WhatsApp). Sin inicio de sesión: el acceso
 * depende del código aleatorio del link. Los borradores no se publican.
 */
class PublicQuoteController extends Controller
{
    public function show(Request $request, string $token, QuotePdf $pdf, QuoteService $service): View
    {
        $quote = $this->find($token);

        // Las visitas del equipo (con sesión en el CRM) no cuentan como vistas del cliente.
        if (! Auth::check()) {
            $service->registerClientView($quote);
        }

        $replacement = null;
        if ($quote->status === QuoteStatus::Replaced) {
            $replacement = Quote::where('opportunity_id', $quote->opportunity_id)
                ->where('folio', $quote->folio)
                ->where('version', '>', $quote->version)
                ->where('status', '!=', QuoteStatus::Draft->value)
                ->orderByDesc('version')
                ->first();
        }

        return view('public.quote', [
            ...$pdf->content($quote),
            'expired' => $quote->isExpired(),
            'canRespond' => $quote->clientCanRespond(),
            'daysLeft' => $quote->valid_until ? BusinessDays::remaining($quote->valid_until) : null,
            'replacement' => $replacement,
            'reasons' => Quote::REJECT_REASONS,
            'isTeam' => Auth::check(),
        ]);
    }

    public function pdf(string $token, QuotePdf $pdf): Response
    {
        $quote = $this->find($token);

        return $pdf->make($quote)->download($quote->pdfFilename());
    }

    public function accept(Request $request, string $token, QuoteService $service): RedirectResponse
    {
        $quote = $this->find($token);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'agree' => ['accepted'],
        ], [
            'name.required' => 'Escribe el nombre de quien acepta.',
            'name.min' => 'Escribe el nombre completo de quien acepta.',
            'agree.accepted' => 'Confirma que aceptas el alcance, el desglose y las condiciones de pago.',
        ]);

        return $this->respond($quote, fn () => $service->acceptByClient($quote, $data['name'], $request->ip(), $request->userAgent()), 'aceptada');
    }

    public function reject(Request $request, string $token, QuoteService $service): RedirectResponse
    {
        $quote = $this->find($token);
        $data = $request->validate([
            'reason' => ['nullable', Rule::in(array_keys(Quote::REJECT_REASONS))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->respond($quote, fn () => $service->rejectByClient($quote, $data['reason'] ?? null, $data['comment'] ?? null, $request->ip(), $request->userAgent()), 'rechazada');
    }

    private function respond(Quote $quote, callable $action, string $result): RedirectResponse
    {
        try {
            $action();
        } catch (ValidationException $e) {
            return redirect()->route('public.quote', $quote->public_token)->withErrors($e->errors());
        }

        return redirect()->route('public.quote', $quote->public_token)->with('responded', $result);
    }

    private function find(string $token): Quote
    {
        abort_unless(strlen($token) === 40, 404);

        $quote = Quote::where('public_token', $token)->firstOrFail();
        abort_unless($quote->isShareable(), 404);

        return $quote;
    }
}
