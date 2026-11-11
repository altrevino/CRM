<?php

namespace App\Services;

use App\Models\PriceList;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * PDF de la cotización. Usa solo importes guardados en la cotización y los textos
 * de la versión de tarifa con que se emitió, así un cambio posterior de
 * configuración no altera documentos ya emitidos.
 */
class QuotePdf
{
    public function make(Quote $quote): DomPdf
    {
        return Pdf::loadView('pdf.quote', $this->data($quote))->setPaper('letter')->setOption([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'dpi' => 96,
        ]);
    }

    /** HTML que se convierte en PDF (útil para pruebas y revisión). */
    public function html(Quote $quote): string
    {
        return view('pdf.quote', $this->data($quote))->render();
    }

    private function data(Quote $quote): array
    {
        $quote->loadMissing(['opportunity.ranch.client', 'opportunity.ranch.state']);

        // Cotizaciones previas al tarifario no tienen copia de textos: se usan los vigentes.
        $terms = $quote->pricing_snapshot ?? PriceList::current()->snapshot();

        return [
            'quote' => $quote,
            'ranch' => $quote->opportunity->ranch,
            'client' => $quote->opportunity->ranch->client,
            'business' => $terms['business'] ?? [],
            'scope' => $this->lines($terms['service_scope'] ?? ''),
            'separateCharges' => trim((string) ($terms['separate_charges'] ?? '')),
            'paymentTerms' => trim((string) ($quote->payment_terms ?? '')),
            'logo' => 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents(public_path('favicon.svg'))),
        ];
    }

    /** @return list<string> */
    private function lines(string $text): array
    {
        return collect(preg_split('/\R/', $text))
            ->map(fn ($l) => trim(ltrim(trim($l), '-•*')))
            ->filter()
            ->values()
            ->all();
    }
}
