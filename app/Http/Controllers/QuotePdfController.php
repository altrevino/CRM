<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\QuotePdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/** Descarga el PDF de una cotización. No cambia su estado ni la envía al cliente. */
class QuotePdfController extends Controller
{
    public function download(Quote $quote, QuotePdf $pdf): Response
    {
        Gate::authorize('view', $quote);

        return $pdf->make($quote)->download($quote->pdfFilename());
    }
}
