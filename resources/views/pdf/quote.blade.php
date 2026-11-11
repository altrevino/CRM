{{--
    Plantilla del PDF de cotización (provisional: falta validarla contra COT-159.pdf).
    Solo usa importes guardados en la cotización. No muestra notas internas, motivos de
    ajustes o descuentos ni el detalle del cálculo progresivo.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }} · {{ $business['name'] ?? 'Espectro Soluciones' }}</title>
<style>
    @page { margin: 34px 40px 46px 40px; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1e293b; line-height: 1.45; margin: 0; }
    .brand { color: #1f625b; }
    .muted { color: #64748b; }
    .small { font-size: 9px; }
    table { border-collapse: collapse; width: 100%; }
    .header td { vertical-align: top; }
    .logo { width: 46px; height: 46px; }
    .company { font-size: 17px; font-weight: bold; color: #0f172a; margin: 0; }
    .tagline { font-size: 10px; color: #237a70; margin: 1px 0 0; }
    .doc-title { font-size: 20px; font-weight: bold; color: #1f625b; letter-spacing: 1px; text-align: right; margin: 0; }
    .meta { text-align: right; font-size: 10px; }
    .meta strong { color: #0f172a; }
    .rule { height: 3px; background: #237a70; margin: 12px 0 14px; }
    .box { border: 1px solid #d7e3e1; border-radius: 6px; padding: 9px 11px; }
    .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: .6px; color: #64748b; }
    .value { font-size: 11px; font-weight: bold; color: #0f172a; }
    h3 { font-size: 11px; color: #1f625b; text-transform: uppercase; letter-spacing: .8px; margin: 14px 0 5px; }
    ul { margin: 0; padding-left: 15px; }
    li { margin-bottom: 2px; }
    .items th { background: #1f625b; color: #fff; font-size: 9.5px; text-align: left; padding: 6px 8px; }
    .items th.num, .items td.num { text-align: right; }
    .items td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; }
    .items .discount td { color: #b45309; }
    .totals td { padding: 4px 8px; font-size: 10.5px; }
    .totals .grand td { font-size: 14px; font-weight: bold; color: #fff; background: #237a70; padding: 8px; }
    .footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 8.5px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    .nobreak { page-break-inside: avoid; }
</style>
</head>
<body>
@php
    $location = collect([$ranch->municipality, $ranch->state?->name])->filter()->implode(', ');
    $contact = collect([$business['whatsapp'] ?? null ? 'WhatsApp '.$business['whatsapp'] : null, $business['email'] ?? null, $business['website'] ?? null])->filter()->implode('  ·  ');
@endphp

<div class="footer">{{ $business['name'] ?? '' }}@if ($contact) · {{ $contact }}@endif</div>

<table class="header">
    <tr>
        <td style="width: 56px;"><img src="{{ $logo }}" class="logo" alt=""></td>
        <td>
            <p class="company">{{ $business['name'] ?? 'Espectro Soluciones' }}</p>
            <p class="tagline">Censos térmicos de fauna</p>
            @if (! empty($business['address']))<p class="small muted" style="margin: 2px 0 0;">{{ $business['address'] }}</p>@endif
        </td>
        <td class="meta">
            <p class="doc-title">COTIZACIÓN</p>
            <div>Folio <strong>{{ $quote->number }}</strong></div>
            <div>Fecha de emisión <strong>{{ fecha($quote->issued_at) }}</strong></div>
            @if ($quote->valid_until)<div>Vigente hasta <strong>{{ fecha($quote->valid_until) }}</strong></div>@endif
        </td>
    </tr>
</table>
<div class="rule"></div>

<table>
    <tr>
        <td style="width: 50%; padding-right: 6px; vertical-align: top;">
            <div class="box">
                <div class="label">Cliente</div>
                <div class="value">{{ $client->name }}</div>
                <div class="label" style="margin-top: 6px;">Rancho</div>
                <div class="value">{{ $ranch->name }}</div>
                @if ($location)<div class="muted">{{ $location }}</div>@endif
            </div>
        </td>
        <td style="width: 50%; padding-left: 6px; vertical-align: top;">
            <div class="box">
                <div class="label">Superficie a censar</div>
                <div class="value">{{ hectareas($quote->hectares) }}</div>
                <div class="label" style="margin-top: 6px;">Fecha tentativa del servicio</div>
                <div class="value">{{ $quote->tentative_service_date ? fecha($quote->tentative_service_date) : 'Por confirmar' }}</div>
            </div>
        </td>
    </tr>
</table>

@if ($scope)
    <h3>Alcance del servicio</h3>
    <ul>
        @foreach ($scope as $line)<li>{{ $line }}</li>@endforeach
    </ul>
@endif
@if ($separateCharges)
    <p class="muted" style="margin: 6px 0 0;"><strong style="color:#334155;">Se cobra por separado:</strong> {{ $separateCharges }}</p>
@endif

<h3>Desglose</h3>
<table class="items nobreak">
    <thead><tr><th>Concepto</th><th class="num" style="width: 130px;">Importe (MXN)</th></tr></thead>
    <tbody>
        <tr><td>Servicio de censo térmico · {{ hectareas($quote->hectares) }}</td><td class="num">{{ money($quote->service_amount) }}</td></tr>
        @if ((float) $quote->logistics_amount > 0 || $quote->pricing_snapshot)
            <tr><td>Traslado y movilización</td><td class="num">{{ money($quote->logistics_amount) }}</td></tr>
        @endif
        @if ((float) $quote->travel_expenses > 0)
            <tr><td>Viáticos</td><td class="num">{{ money($quote->travel_expenses) }}</td></tr>
        @endif
        @if ($quote->hasDiscount())
            <tr class="discount"><td>{{ $quote->discountLabel() }}</td><td class="num">−{{ money($quote->discount_amount) }}</td></tr>
        @endif
    </tbody>
</table>
<table class="totals nobreak" style="margin-top: 4px;">
    <tr><td style="width: 45%;"></td><td class="muted" style="white-space: nowrap;">Subtotal{{ $quote->hasDiscount() ? ' (con descuento)' : '' }}</td><td style="text-align: right; width: 150px; white-space: nowrap;">{{ money($quote->subtotal) }}</td></tr>
    <tr><td></td><td class="muted">{{ $quote->apply_vat ? 'IVA '.rtrim(rtrim(number_format((float) $quote->vat_rate, 2), '0'), '.').'%' : 'IVA' }}</td><td style="text-align: right;">{{ $quote->apply_vat ? money($quote->vat_amount) : 'No aplica' }}</td></tr>
    <tr class="grand"><td style="background: #fff;"></td><td>Total</td><td style="text-align: right; white-space: nowrap;">{{ money($quote->total) }} MXN</td></tr>
</table>

@if ($paymentTerms)
    <div class="nobreak">
        <h3>Condiciones de pago</h3>
        <div>{!! nl2br(e($paymentTerms)) !!}</div>
    </div>
@endif

@if ($quote->observations)
    <div class="nobreak">
        <h3>Observaciones</h3>
        <div>{!! nl2br(e($quote->observations)) !!}</div>
    </div>
@endif

<div class="nobreak">
    <h3>Contacto</h3>
    <div>
        {{ $business['name'] ?? '' }}
        @if (! empty($business['whatsapp']))<br>WhatsApp: {{ $business['whatsapp'] }}@endif
        @if (! empty($business['email']))<br>Correo: {{ $business['email'] }}@endif
        @if (! empty($business['website']))<br>{{ $business['website'] }}@endif
    </div>
    @if ($quote->valid_until)
        <p class="small muted" style="margin-top: 8px;">Precios en pesos mexicanos. Cotización válida hasta el {{ fecha($quote->valid_until) }}.</p>
    @endif
</div>
</body>
</html>
