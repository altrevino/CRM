{{-- Botones de respuesta de la página pública (se usan arriba y al final). --}}
@if ($showActions)
    <div class="actions">
        <button type="button" class="btn btn-accept" data-open="m-accept" @disabled($isTeam)>Aceptar cotización</button>
        <button type="button" class="btn btn-reject" data-open="m-reject" @disabled($isTeam)>Rechazar</button>
    </div>
@elseif ($expired && $waUpdate)
    <div class="actions"><a class="btn btn-accept" href="{{ $waUpdate }}">Solicitar cotización actualizada</a></div>
@elseif ($q->status === \App\Enums\QuoteStatus::Accepted && $waAccepted)
    <div class="actions"><a class="btn btn-accept" href="{{ $waAccepted }}">Coordinar anticipo por WhatsApp</a></div>
@endif
