@php
    $q = $quote;
    $accepted = $q->client_response === 'aceptada';
@endphp
{{ $q->opportunity->ranch->client->name }} {{ $accepted ? 'ACEPTÓ' : 'RECHAZÓ' }} la cotización {{ $q->number }} desde el link.

Rancho: {{ $q->opportunity->ranch->name }} ({{ $q->opportunity->ranch->location() }})
Superficie: {{ hectareas($q->hectares) }}
Total: {{ money($q->total) }} MXN {{ $q->apply_vat ? 'con IVA' : 'sin IVA' }}
Fecha: {{ fecha_hora($q->client_responded_at) }}
@if ($accepted)
Aceptó: {{ $q->client_responded_by }}

Siguiente paso: solicitar el anticipo y agendar la fecha del censo.
@else
Motivo: {{ \App\Models\Quote::REJECT_REASONS[$q->client_response_reason] ?? 'Sin motivo' }}
@if ($q->client_response_comment)
Comentario: {{ $q->client_response_comment }}
@endif
@endif

Ver en el CRM: {{ route('quotes.show', $q) }}
