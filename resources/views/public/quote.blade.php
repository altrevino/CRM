@php
    $q = $quote;
    $S = \App\Enums\QuoteStatus::class;
    $complete = $q->opportunity->service_type === \App\Enums\ServiceType::Complete;
    $modality = $complete ? 'Levantamiento integral' : 'Censo '.mb_strtolower($q->opportunity->service_type->label());
    $ha = (float) $q->hectares;
    $haText = number_format($ha, fmod($ha, 1.0) == 0.0 ? 0 : 2);
    $pesos = fn ($v) => '$'.number_format((float) $v, fmod(round((float) $v, 2), 1.0) == 0.0 ? 0 : 2);
    $vatPct = rtrim(rtrim(number_format((float) $q->vat_rate, 2), '0'), '.');
    $location = $ranch->location() ?: 'Por confirmar';
    $company = $business['name'] ?? 'Espectro Soluciones';
    $terms = collect(preg_split('/\R/', $paymentTerms))->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values();
    $waDigits = preg_replace('/\D+/', '', $business['whatsapp'] ?? '');
    $wa = fn (string $text) => $waDigits ? 'https://wa.me/'.$waDigits.'?text='.rawurlencode($text) : null;
    $waQuestion = $wa("Hola, tengo una pregunta sobre la cotización {$q->number} ({$ranch->name}).");
    $waUpdate = $wa("Hola, la cotización {$q->number} ({$ranch->name}) perdió su vigencia. ¿Me la pueden actualizar?");
    $waAccepted = $wa("Hola, acepté la cotización {$q->number} ({$ranch->name}). ¿Cómo hago el anticipo para reservar la fecha?");
    $assets = asset('c/assets');
    $showActions = $canRespond;
    $acceptError = $errors->has('name') || $errors->has('agree');
    $methodology = [
        ['Planeación', 'Diseño de rutas, altura y separación de transectos según vegetación, relieve y características del predio.'],
        ['Levantamiento', 'Barrido térmico sistemático mediante rutas planificadas, aprovechando las condiciones de mayor contraste térmico.'],
        ['Detección y validación', 'Identificación de fauna mediante imagen térmica y confirmación visual cuando las condiciones lo permiten.'],
        ['Bitácora digital', 'Registro georreferenciado de detecciones, incluyendo especie, sexo y clasificación disponible.'],
        ['Auditoría de detecciones', 'Revisión digital de registros para identificar posibles duplicidades, verificar clasificaciones y consolidar el conteo.'],
        ['Análisis y reporte', 'Procesamiento de datos para generar densidades, estructura poblacional, mapas y recomendaciones de manejo.'],
    ];
    $deliverables = [
        ['Conteo por especie', 'Inventario de ejemplares detectados y validados, clasificados por especie, sexo y edad cuando las condiciones lo permiten.'],
        ['Mapa georreferenciado', 'Distribución espacial de las detecciones para identificar zonas de concentración y presencia de fauna.'],
        ['Análisis de densidades', 'Cálculo de densidades por especie y superficie censada, con indicadores de abundancia poblacional.'],
        ['Estructura poblacional', 'Análisis de la relación macho-hembra, presencia de crías y composición de las poblaciones registradas.'],
        ['Dashboard de resultados', 'Visualización de densidades, estructura poblacional y distribución de fauna mediante gráficas e indicadores.'],
        ['Reporte técnico', 'Resultados consolidados, metodología, mapas, evidencia visual, limitaciones y recomendaciones de manejo.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title>Cotización {{ $q->number }} · {{ $ranch->name }}</title>
<meta name="description" content="Censo térmico de fauna · {{ $haText }} ha · {{ $location }}">
<meta property="og:type" content="website">
<meta property="og:title" content="Cotización {{ $q->number }} · {{ $ranch->name }}">
<meta property="og:description" content="{{ $company }} · Censo térmico de fauna sobre {{ $haText }} ha en {{ $location }}">
<meta property="og:image" content="{{ $assets }}/venado.jpg">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="theme-color" content="#1a1410">
<link rel="icon" href="{{ asset('favicon.svg') }}">
<style>
  @font-face { font-family: 'Oswald'; font-weight: 300; font-display: swap; src: url('{{ $assets }}/Oswald-Light.woff2') format('woff2'); }
  @font-face { font-family: 'Oswald'; font-weight: 400; font-display: swap; src: url('{{ $assets }}/Oswald-Regular.woff2') format('woff2'); }
  @font-face { font-family: 'Oswald'; font-weight: 700; font-display: swap; src: url('{{ $assets }}/Oswald-Bold.woff2') format('woff2'); }
  @font-face { font-family: 'SSerif'; font-weight: 400; font-style: normal; font-display: swap; src: url('{{ $assets }}/SourceSerif4-Regular.woff2') format('woff2'); }
  @font-face { font-family: 'SSerif'; font-weight: 400; font-style: italic; font-display: swap; src: url('{{ $assets }}/SourceSerif4-It.woff2') format('woff2'); }

  :root { --ink: #1a1410; --card: #2b231f; --cream: #fdfaf6; --sand: #f2ede6; --tan: #d6c2a1; --wine: #8b2e2e; --gold: #b9914b; --muted: #8f8378; --line: #e4dccf; --ok: #2f6b4a; }
  * { box-sizing: border-box; }
  html { -webkit-text-size-adjust: 100%; }
  body { margin: 0; background: var(--cream); color: var(--ink); font-family: 'SSerif', Georgia, serif; font-size: 16px; line-height: 1.45; }
  a { color: inherit; }
  .osw { font-family: 'Oswald', 'Arial Narrow', sans-serif; }
  .wrap { max-width: 960px; margin: 0 auto; padding: 0 18px; }
  section { padding: 28px 0; }
  section + section { border-top: 1px solid var(--line); }
  h2 { font-family: 'Oswald'; font-weight: 700; text-transform: uppercase; font-size: clamp(24px, 5vw, 32px); line-height: 1.05; margin: 0 0 14px; }
  h2 .r { color: var(--wine); }

  .top { background: var(--ink); color: var(--tan); }
  .top .wrap { display: flex; align-items: center; gap: 12px; padding-top: 10px; padding-bottom: 10px; }
  .top img { height: 40px; flex: none; }
  .top .who { flex: 1; min-width: 0; font-family: 'Oswald'; line-height: 1.25; }
  .top .who b { display: block; color: #fff; font-size: 17px; font-weight: 400; letter-spacing: .5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .top .who span { font-size: 13px; }
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-family: 'Oswald'; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; font-size: 15px; border-radius: 6px; padding: 12px 20px; border: 0; cursor: pointer; text-decoration: none; text-align: center; }
  .btn-accept { background: var(--gold); color: var(--ink); }
  .btn-reject { background: transparent; color: inherit; border: 1.5px solid currentColor; }
  .btn-pdf { background: transparent; color: var(--tan); border: 1px solid #ffffff40; padding: 8px 12px; font-size: 13px; font-weight: 400; flex: none; }
  .btn[disabled] { opacity: .45; cursor: not-allowed; }

  .team { background: #3b2f8f; color: #fff; font-family: 'Oswald'; font-size: 13px; text-align: center; padding: 6px 14px; }
  .banner { padding: 12px 18px; font-family: 'Oswald'; letter-spacing: .3px; text-align: center; font-size: 15px; }
  .banner a { color: #fff; font-weight: 700; }
  .banner.ok { background: var(--ok); color: #fff; }
  .banner.no { background: #5a4a3c; color: #fff; }
  .banner.exp { background: var(--wine); color: #fff; }

  .meta { display: flex; flex-wrap: wrap; gap: 4px 16px; font-family: 'Oswald'; font-size: 14px; color: #5a4a3c; margin: -4px 0 16px; }
  .meta .chip { padding: 2px 10px; border-radius: 99px; background: #2f6b4a1a; color: var(--ok); border: 1px solid #2f6b4a55; }
  .meta .chip.exp { background: #8b2e2e14; color: var(--wine); border-color: #8b2e2e55; }

  .inv { display: grid; grid-template-columns: 1fr; gap: 20px; }
  @media (min-width: 820px) { .inv { grid-template-columns: 1.25fr 1fr; gap: 32px; } }
  table.bd { width: 100%; border-collapse: collapse; }
  .bd td { padding: 9px 0; border-bottom: 1px solid var(--line); vertical-align: top; }
  .bd td:last-child { text-align: right; font-family: 'Oswald'; font-size: 17px; white-space: nowrap; padding-left: 12px; }
  .bd td small { display: block; color: var(--muted); font-size: 13px; }
  .bd .disc td { color: #8b4a12; }
  .bd .sub td { color: #5a4a3c; }
  .total { margin-top: 12px; background: var(--ink); color: var(--tan); border-radius: 8px; padding: 14px 18px; display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
  .total .l { font-family: 'Oswald'; font-weight: 700; font-size: 20px; letter-spacing: 1px; }
  .total .v { font-family: 'Oswald'; font-weight: 700; font-size: clamp(30px, 7vw, 38px); color: #fff; }
  .total .v small { font-size: 14px; font-weight: 400; color: var(--tan); }
  .side { background: var(--sand); border-radius: 8px; padding: 16px 18px; }
  .side h4 { font-family: 'Oswald'; font-weight: 700; text-transform: uppercase; font-size: 15px; margin: 0 0 6px; letter-spacing: .5px; }
  .side ul { margin: 0 0 12px; padding-left: 18px; }
  .side li { margin-bottom: 3px; }
  .side p { margin: 8px 0 0; font-size: 14px; color: #5a4a3c; }
  .actions { display: flex; gap: 10px; margin-top: 14px; }
  .actions .btn { flex: 1; }
  .hint { font-size: 14px; color: var(--muted); margin-top: 8px; text-align: center; }

  .list { display: grid; grid-template-columns: 1fr; gap: 0 28px; }
  @media (min-width: 640px) { .list { grid-template-columns: 1fr 1fr; } }
  @media (min-width: 900px) { .list { grid-template-columns: 1fr 1fr 1fr; } }
  .item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); }
  .item .n { font-family: 'Oswald'; font-weight: 700; color: var(--wine); font-size: 24px; line-height: 1; min-width: 30px; }
  .item h4 { font-family: 'Oswald'; font-weight: 700; text-transform: uppercase; margin: 0 0 2px; font-size: 15px; letter-spacing: .3px; }
  .item p { margin: 0; font-family: 'Oswald'; font-weight: 300; font-size: 15px; line-height: 1.35; }
  .final { margin-top: 14px; font-family: 'Oswald'; font-weight: 300; font-size: 15px; background: var(--sand); border-radius: 8px; padding: 10px 14px; }
  .final b { font-weight: 700; }

  footer { text-align: center; font-family: 'Oswald'; font-size: 14px; color: var(--muted); padding: 18px 18px 110px; border-top: 1px solid var(--line); }
  footer a { color: var(--ink); }
  @media (min-width: 900px) { footer { padding-bottom: 24px; } }

  .sticky { position: fixed; left: 0; right: 0; bottom: 0; z-index: 40; background: #1a1410f2; border-top: 1px solid #ffffff22; padding: 9px 14px calc(9px + env(safe-area-inset-bottom)); display: flex; gap: 8px; align-items: center; color: var(--tan); }
  .sticky .t { flex: 1; font-family: 'Oswald'; line-height: 1.1; font-size: 12px; }
  .sticky .t b { display: block; color: #fff; font-size: 20px; }
  .sticky .btn { padding: 10px 14px; font-size: 14px; }
  @media (min-width: 900px) { .sticky { display: none; } }

  .watermark { position: fixed; inset: 0; z-index: 30; pointer-events: none; overflow: hidden; }
  .watermark div { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(-28deg); font-family: 'Oswald'; font-weight: 700; font-size: clamp(52px, 14vw, 170px); color: #8b2e2e; opacity: .16; white-space: nowrap; letter-spacing: 6px; border: 9px solid #8b2e2e; padding: 0 32px; border-radius: 18px; }

  .modal { position: fixed; inset: 0; z-index: 70; background: #1a1410b3; display: none; align-items: flex-end; justify-content: center; }
  .modal.open { display: flex; }
  .sheet { background: var(--cream); width: 100%; max-width: 500px; border-radius: 16px 16px 0 0; padding: 22px 22px calc(22px + env(safe-area-inset-bottom)); max-height: 92vh; overflow-y: auto; }
  @media (min-width: 640px) { .modal { align-items: center; } .sheet { border-radius: 14px; } }
  .sheet h3 { font-family: 'Oswald'; font-weight: 700; text-transform: uppercase; font-size: 22px; margin: 0 0 6px; }
  .sheet label.f { display: block; font-family: 'Oswald'; font-size: 13px; letter-spacing: .5px; margin: 12px 0 5px; text-transform: uppercase; }
  .sheet input[type=text], .sheet textarea, .sheet select { width: 100%; font: inherit; font-size: 16px; padding: 10px 12px; border: 1px solid #cfc4b4; border-radius: 6px; background: #fff; color: var(--ink); }
  .sheet .chk { display: flex; gap: 10px; align-items: flex-start; font-size: 15px; margin-top: 12px; }
  .sheet .chk input { margin-top: 4px; width: 18px; height: 18px; flex: none; }
  .sheet .row { display: flex; gap: 10px; margin-top: 18px; }
  .sheet .row .btn { flex: 1; }
  .sheet .btn-reject { color: var(--ink); }
  .recap { background: var(--sand); border-radius: 8px; padding: 10px 12px; font-size: 15px; margin-top: 6px; }
  .err { color: #b42318; font-size: 14px; margin-top: 5px; }
</style>
</head>
<body>

@if ($isTeam)
    <div class="team">Vista del equipo: tus visitas no cuentan y los botones de respuesta están desactivados.</div>
@endif

@if ($expired)
    <div class="watermark" aria-hidden="true"><div>SIN VIGENCIA</div></div>
@endif

<header class="top">
    <div class="wrap">
        <img src="{{ $assets }}/logo.png" alt="{{ $company }}">
        <div class="who"><b>{{ $ranch->name }}</b><span>Cotización {{ $q->number }} · {{ $client->name }}</span></div>
        <a class="btn btn-pdf" href="{{ route('public.quote.pdf', $q->public_token) }}">PDF ↓</a>
    </div>
</header>

@if (session('responded') === 'aceptada' || ($q->status === $S::Accepted && $q->client_response === 'aceptada'))
    <div class="banner ok">✔ Aceptada el {{ fecha($q->client_responded_at) }} por {{ $q->client_responded_by }}. Te contactaremos para el anticipo.@if ($waAccepted) <a href="{{ $waAccepted }}">WhatsApp</a>@endif</div>
@elseif ($q->status === $S::Accepted)
    <div class="banner ok">✔ Cotización aceptada. Gracias por tu confianza.</div>
@elseif ($q->status === $S::Rejected)
    <div class="banner no">Cotización rechazada{{ $q->client_responded_at ? ' el '.fecha($q->client_responded_at) : '' }}. Si cambian tus planes, escríbenos.</div>
@elseif ($q->status === $S::Replaced)
    <div class="banner no">Esta cotización fue reemplazada por una versión más reciente.@if ($replacement) <a href="{{ $replacement->publicUrl() }}">Ver {{ $replacement->number }}</a>@endif</div>
@elseif ($expired)
    <div class="banner exp">La vigencia de esta cotización terminó el {{ fecha($q->valid_until) }}.@if ($waUpdate) <a href="{{ $waUpdate }}">Solicitar actualización</a>@endif</div>
@endif
@error('quote')<div class="banner exp">{{ $message }}</div>@enderror

{{-- 1. Desglose de la inversión --}}
<section id="desglose">
    <div class="wrap">
        <h2>Desglose de la <span class="r">inversión</span></h2>
        <div class="meta">
            <span>{{ $modality }} · {{ $haText }} ha · {{ $location }}</span>
            @if ($q->valid_until)
                @if ($expired)
                    <span class="chip exp">Sin vigencia · terminó el {{ fecha($q->valid_until) }}</span>
                @elseif ($canRespond)
                    <span class="chip">Vigente hasta el {{ fecha($q->valid_until) }} · {{ $daysLeft === 0 ? 'último día' : ($daysLeft === 1 ? '1 día hábil' : "{$daysLeft} días hábiles") }}</span>
                @else
                    <span>Vigencia hasta el {{ fecha($q->valid_until) }}</span>
                @endif
            @endif
        </div>
        <div class="inv">
            <div>
                <table class="bd">
                    <tr><td>Servicio de censo térmico<small>{{ $haText }} ha{{ $scope ? ' · '.implode(' · ', $scope) : '' }}</small></td><td>{{ money($q->service_amount) }}</td></tr>
                    <tr><td>Traslado y movilización</td><td>{{ money($q->logistics_amount) }}</td></tr>
                    @if ((float) $q->travel_expenses > 0)<tr><td>Viáticos</td><td>{{ money($q->travel_expenses) }}</td></tr>@endif
                    @if ($q->hasDiscount())<tr class="disc"><td>{{ $q->discountLabel() }}</td><td>−{{ money($q->discount_amount) }}</td></tr>@endif
                    <tr class="sub"><td>Subtotal</td><td>{{ money($q->subtotal) }}</td></tr>
                    <tr class="sub"><td>{{ $q->apply_vat ? 'IVA '.$vatPct.'%' : 'IVA' }}</td><td>{{ $q->apply_vat ? money($q->vat_amount) : 'No aplica' }}</td></tr>
                </table>
                <div class="total"><span class="l">TOTAL</span><span class="v">{{ $pesos($q->total) }} <small>MXN {{ $q->apply_vat ? 'IVA incluido' : 'sin IVA' }}</small></span></div>
            </div>
            <div>
                <div class="side">
                    @if ($terms->isNotEmpty())
                        <h4>Condiciones de pago</h4>
                        <ul>@foreach ($terms as $t)<li>{{ $t }}</li>@endforeach</ul>
                    @endif
                    @if ($q->tentative_service_date)<p><b>Fecha tentativa:</b> {{ fecha($q->tentative_service_date) }}</p>@endif
                    @if ($q->observations)<p><b>Observaciones:</b> {{ $q->observations }}</p>@endif
                    @if ($separateCharges)<p>{{ $separateCharges }}</p>@endif
                </div>
                @include('public.partials.quote-actions')
                @if ($showActions && $waQuestion)
                    <div class="hint">¿Dudas? <a href="{{ $waQuestion }}">Escríbenos por WhatsApp</a></div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- 2. Metodología --}}
<section>
    <div class="wrap">
        <h2><span class="r">Metodología</span> y protocolo</h2>
        <div class="list">
            @foreach ($methodology as $i => [$t, $d])
                <div class="item"><span class="n">{{ sprintf('%02d', $i + 1) }}</span><div><h4>{{ $t }}</h4><p>{{ $d }}</p></div></div>
            @endforeach
        </div>
    </div>
</section>

{{-- 3. Entregables --}}
<section>
    <div class="wrap">
        <h2>Lo que <span class="r">recibes</span></h2>
        <div class="list">
            @foreach ($deliverables as $i => [$t, $d])
                <div class="item"><span class="n">{{ sprintf('%02d', $i + 1) }}</span><div><h4>{{ $t }}</h4><p>{{ $d }}</p></div></div>
            @endforeach
        </div>
        <div class="final"><b>Entregable final:</b> reporte técnico + dashboard de resultados + mapa interactivo + tabla de detecciones + evidencia térmica y RGB.</div>
    </div>
</section>

<footer>
    {{ $company }}
    @if ($waQuestion) · <a href="{{ $waQuestion }}">WhatsApp {{ $business['whatsapp'] }}</a>@endif
    @if (! empty($business['email'])) · <a href="mailto:{{ $business['email'] }}">{{ $business['email'] }}</a>@endif
</footer>

@if ($showActions)
    <div class="sticky">
        <div class="t">Total {{ $q->apply_vat ? 'IVA incluido' : 'sin IVA' }}<b>{{ $pesos($q->total) }}</b></div>
        <button type="button" class="btn btn-reject" data-open="m-reject" @disabled($isTeam)>Rechazar</button>
        <button type="button" class="btn btn-accept" data-open="m-accept" @disabled($isTeam)>Aceptar</button>
    </div>

    <div class="modal {{ $acceptError ? 'open' : '' }}" id="m-accept" role="dialog" aria-modal="true" aria-labelledby="m-accept-t">
        <form class="sheet" method="POST" action="{{ route('public.quote.accept', $q->public_token) }}">
            @csrf
            <h3 id="m-accept-t">Aceptar cotización</h3>
            <div class="recap">{{ $q->number }} · {{ $ranch->name }} · {{ $haText }} ha<br><b class="osw" style="font-size: 19px">{{ $pesos($q->total) }} MXN {{ $q->apply_vat ? 'IVA incluido' : 'sin IVA' }}</b></div>
            <label class="f" for="acc-name">Nombre de quien acepta</label>
            <input id="acc-name" type="text" name="name" value="{{ old('name') }}" placeholder="Nombre completo" autocomplete="name" required minlength="3" maxlength="120">
            @error('name')<div class="err">{{ $message }}</div>@enderror
            <label class="chk"><input type="checkbox" name="agree" value="1" required @checked(old('agree'))> Acepto el alcance, el desglose y las condiciones de pago de esta cotización.</label>
            @error('agree')<div class="err">{{ $message }}</div>@enderror
            <div class="row">
                <button type="button" class="btn btn-reject" data-close>Cancelar</button>
                <button type="submit" class="btn btn-accept">Confirmar</button>
            </div>
        </form>
    </div>

    <div class="modal" id="m-reject" role="dialog" aria-modal="true" aria-labelledby="m-reject-t">
        <form class="sheet" method="POST" action="{{ route('public.quote.reject', $q->public_token) }}">
            @csrf
            <h3 id="m-reject-t">Rechazar cotización</h3>
            <p style="margin: 0">Nos ayuda saber el motivo. Es opcional.</p>
            <label class="f" for="rej-reason">Motivo</label>
            <select id="rej-reason" name="reason">
                <option value="">Prefiero no decir</option>
                @foreach ($reasons as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
            <label class="f" for="rej-comment">Comentario</label>
            <textarea id="rej-comment" name="comment" rows="3" maxlength="1000" placeholder="Opcional"></textarea>
            <div class="row">
                <button type="button" class="btn btn-reject" data-close>Cancelar</button>
                <button type="submit" class="btn btn-accept" style="background: var(--ink); color: #fff">Confirmar</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.open).classList.add('open')));
        document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if (e.target === m || e.target.hasAttribute('data-close')) m.classList.remove('open'); }));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open')); });
        document.querySelectorAll('.modal form').forEach(f => f.addEventListener('submit', () => f.querySelector('[type=submit]').disabled = true));
    </script>
@endif
</body>
</html>
