@php
    $q = $quote;
    $S = \App\Enums\QuoteStatus::class;
    $complete = $q->opportunity->service_type === \App\Enums\ServiceType::Complete;
    $modality = $complete ? 'Levantamiento integral' : 'Censo '.mb_strtolower($q->opportunity->service_type->label());
    [$modalityA, $modalityB] = array_pad(explode(' ', $modality, 2), 2, '');
    $ha = (float) $q->hectares;
    $haText = number_format($ha, fmod($ha, 1.0) == 0.0 ? 0 : 2);
    $pesos = fn ($v) => '$'.number_format((float) $v, fmod(round((float) $v, 2), 1.0) == 0.0 ? 0 : 2);
    $vatPct = rtrim(rtrim(number_format((float) $q->vat_rate, 2), '0'), '.');
    $location = $ranch->location() ?: 'Por confirmar';
    $fence = $ranch->fence_type?->label();
    $company = $business['name'] ?? 'Espectro Soluciones';
    $columns = array_values(array_filter([
        ['Superficie', $haText, 'hectáreas'],
        ['Servicio', $pesos($q->service_amount), null],
        ['Logística y movilización', $pesos($q->logistics_amount), null],
        (float) $q->travel_expenses > 0 ? ['Viáticos', $pesos($q->travel_expenses), null] : null,
        $q->hasDiscount() ? [$q->discountLabel(), '−'.$pesos($q->discount_amount), null] : null,
    ]));
    $terms = collect(preg_split('/\R/', $paymentTerms))->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values();
    $waDigits = preg_replace('/\D+/', '', $business['whatsapp'] ?? '');
    $wa = fn (string $text) => $waDigits ? 'https://wa.me/'.$waDigits.'?text='.rawurlencode($text) : null;
    $waQuestion = $wa("Hola, tengo una pregunta sobre la cotización {$q->number} ({$ranch->name}).");
    $waUpdate = $wa("Hola, la cotización {$q->number} ({$ranch->name}) perdió su vigencia. ¿Me la pueden actualizar?");
    $waAccepted = $wa("Hola, acepté la cotización {$q->number} ({$ranch->name}). ¿Cómo hago el anticipo para reservar la fecha?");
    $first = \Illuminate\Support\Str::before(trim($client->name), ' ');
    $assets = asset('c/assets');
    $showActions = $canRespond;
    $acceptError = $errors->has('name') || $errors->has('agree');
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
  @font-face { font-family: 'SSerif'; font-weight: 700; font-style: normal; font-display: swap; src: url('{{ $assets }}/SourceSerif4-Bold.woff2') format('woff2'); }

  :root { --ink: #1a1410; --card: #2b231f; --cream: #fdfaf6; --sand: #f2ede6; --tan: #d6c2a1; --wine: #8b2e2e; --copper: #8a5a3a; --gold: #b9914b; --muted: #8f8378; --line: #e4dccf; --ok: #2f6b4a; }
  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
  body { margin: 0; background: var(--cream); color: var(--ink); font-family: 'SSerif', Georgia, serif; font-size: 17px; line-height: 1.5; }
  a { color: inherit; }
  .osw { font-family: 'Oswald', 'Arial Narrow', sans-serif; }
  .wrap { max-width: 1040px; margin: 0 auto; padding: 0 20px; }
  .kicker { font-family: 'Oswald'; font-size: 13px; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); }
  h2 { font-family: 'Oswald'; font-weight: 700; text-transform: uppercase; font-size: clamp(30px, 6vw, 46px); line-height: 1.02; margin: 8px 0 24px; letter-spacing: -0.5px; }
  h2 .r { color: var(--wine); }
  section { padding: 56px 0; }
  .dark { background: var(--ink); color: var(--tan); }
  .dark h2 { color: var(--tan); }
  .dark h2 .w { color: #fff; }

  .top { background: var(--ink); border-bottom: 1px solid #ffffff14; }
  .top .wrap { display: flex; align-items: center; justify-content: space-between; height: 66px; gap: 12px; }
  .top img { height: 46px; }
  .top .meta { font-family: 'Oswald'; color: var(--tan); font-size: 13px; letter-spacing: 1px; text-align: right; }
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-family: 'Oswald'; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; font-size: 15px; border-radius: 6px; padding: 13px 22px; border: 0; cursor: pointer; text-decoration: none; text-align: center; }
  .btn-accept { background: var(--gold); color: var(--ink); }
  .btn-reject { background: transparent; color: inherit; border: 1.5px solid currentColor; }
  .btn-ghost { background: transparent; color: var(--tan); border: 1px solid #ffffff33; padding: 7px 12px; font-size: 12px; font-weight: 400; }
  .btn[disabled] { opacity: .45; cursor: not-allowed; }

  .team { background: #3b2f8f; color: #fff; font-family: 'Oswald'; font-size: 14px; text-align: center; padding: 8px 16px; }
  .banner { padding: 14px 20px; font-family: 'Oswald'; letter-spacing: .4px; text-align: center; font-size: 16px; }
  .banner a { color: #fff; font-weight: 700; }
  .banner.ok { background: var(--ok); color: #fff; }
  .banner.no { background: #5a4a3c; color: #fff; }
  .banner.exp { background: var(--wine); color: #fff; }

  .hero { background: var(--ink); color: var(--tan); padding: 40px 0 0; }
  .hero .tag { font-style: italic; font-size: 15px; }
  .hero h1 { font-family: 'Oswald'; font-weight: 700; color: #fff; text-transform: uppercase; font-size: clamp(36px, 8vw, 66px); line-height: 1.02; margin: 6px 0 10px; }
  .hero h1 span { color: var(--wine); }
  .hero .bar { width: 90px; height: 3px; background: var(--tan); margin: 6px 0 12px; }
  .hero .sub { font-family: 'Oswald'; letter-spacing: 5px; font-size: clamp(12px, 2.4vw, 17px); text-transform: uppercase; }
  .facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); border-top: 1px solid #8a7a62; margin-top: 36px; }
  .facts div { padding: 18px 12px 18px 0; }
  .facts b { display: block; font-family: 'Oswald'; font-size: 24px; color: #fff; font-weight: 400; letter-spacing: .5px; line-height: 1.2; }
  .facts small { font-family: 'Oswald'; letter-spacing: 2px; text-transform: uppercase; font-size: 12px; }
  .validity { display: inline-flex; align-items: center; gap: 8px; margin-top: 18px; padding: 7px 14px; border-radius: 99px; font-family: 'Oswald'; font-size: 14px; letter-spacing: .5px; background: #2f6b4a33; color: #b7e2c8; border: 1px solid #2f6b4a; }
  .validity.exp { background: #8b2e2e33; color: #f3c2c2; border-color: var(--wine); }
  .validity.neutral { background: #ffffff10; color: var(--tan); border-color: #ffffff33; }

  .summary { background: var(--ink); padding: 8px 0 48px; }
  .sumcard { background: var(--card); border: 1px solid #6b5a4a; border-radius: 10px; padding: 26px; color: var(--tan); }
  .sumcard h3 { font-family: 'Oswald'; font-weight: 700; font-size: 22px; margin: 4px 0 0; }
  .sumcard .scope { font-style: italic; font-size: 15px; margin: 4px 0 18px; }
  .cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px; border-top: 1px solid #8a7a62; padding-top: 18px; text-align: center; }
  .cols small { font-family: 'Oswald'; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; }
  .cols b { display: block; font-family: 'Oswald'; font-size: 30px; color: #fff; font-weight: 700; margin-top: 4px; }
  .total { margin-top: 22px; background: var(--gold); color: var(--ink); border-radius: 6px; padding: 16px 22px; display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 6px 20px; }
  .total .l { font-family: 'Oswald'; font-weight: 700; font-size: 30px; }
  .total .v { font-family: 'Oswald'; font-weight: 700; font-size: clamp(34px, 7vw, 46px); }
  .total .v small { font-size: 16px; font-weight: 400; }
  .total .bk { width: 100%; font-family: 'Oswald'; font-size: 14px; }
  .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 22px; }
  .actions .btn { flex: 1 1 200px; }
  .terms { display: flex; flex-wrap: wrap; gap: 8px 24px; margin-top: 18px; font-size: 15px; }
  .terms span::before { content: '•'; margin-right: 8px; color: var(--gold); }
  .hint { font-size: 14px; color: var(--muted); margin-top: 12px; }
  .hint a { color: var(--tan); }

  .ctx { position: relative; overflow: hidden; }
  .ctx .photo { position: absolute; inset: 0 0 0 auto; width: 55%; background: url('{{ $assets }}/venado.jpg') right center / cover no-repeat; }
  .ctx .wrap { position: relative; }
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); max-width: 620px; }
  .stats div { padding: 4px 18px; border-left: 2px solid var(--ink); }
  .stats div:first-child { border-left: 0; padding-left: 0; }
  .stats small { font-family: 'Oswald'; text-transform: uppercase; font-size: 14px; }
  .stats b { display: block; font-family: 'Oswald'; font-size: 26px; font-weight: 700; text-transform: uppercase; line-height: 1.15; }
  .box { background: var(--card); color: #f1ece6; border-radius: 6px; padding: 18px 22px; margin-top: 16px; max-width: 620px; }
  .box h4 { font-family: 'Oswald'; font-weight: 700; margin: 0 0 4px; text-transform: uppercase; font-size: 17px; }
  .box p { margin: 0; font-family: 'Oswald'; font-weight: 300; font-size: 17px; }

  .grid3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
  @media (min-width: 760px) { .grid3 { grid-template-columns: repeat(3, 1fr); } }
  .item { padding: 18px 20px 18px 0; border-bottom: 1px solid var(--line); display: flex; gap: 14px; }
  .item .n { font-family: 'Oswald'; font-weight: 700; color: var(--wine); font-size: 34px; line-height: 1; }
  .item h4 { font-family: 'Oswald'; font-weight: 700; text-transform: uppercase; margin: 0 0 4px; font-size: 17px; }
  .item p { margin: 0; font-family: 'Oswald'; font-weight: 300; font-size: 16px; line-height: 1.4; }
  .note { background: var(--card); color: #f1ece6; border-radius: 6px; padding: 18px 22px; margin-top: 26px; font-family: 'Oswald'; font-weight: 300; }
  .note b { font-weight: 700; }
  .stat4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 22px; text-align: center; margin: 10px 0 26px; }
  .stat4 b { display: block; font-family: 'Oswald'; font-weight: 700; font-size: 38px; color: var(--tan); line-height: 1.05; }
  .stat4 span { font-style: italic; font-size: 14px; }
  .tech { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 28px; }
  .tech img { width: 100%; border-radius: 6px; display: block; }
  .tech h4 { font-family: 'Oswald'; font-weight: 700; color: #8c5a3c; text-align: center; font-size: 22px; margin: 10px 0 4px; }
  .tech p { font-family: 'Oswald'; font-weight: 300; font-size: 17px; margin: 0; }

  table.bd { width: 100%; border-collapse: collapse; font-size: 16px; }
  .bd td { padding: 12px 0; border-bottom: 1px solid var(--line); vertical-align: top; }
  .bd td:last-child { text-align: right; font-family: 'Oswald'; font-size: 18px; white-space: nowrap; padding-left: 12px; }
  .bd td small { display: block; color: var(--muted); font-size: 13px; }
  .bd .disc td { color: #8b4a12; }
  .bd .tot td { border-bottom: 0; font-family: 'Oswald'; font-weight: 700; font-size: 22px; padding-top: 16px; }
  .two { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; }
  .incl { padding-left: 20px; margin: 0; }
  .incl li { margin-bottom: 6px; }
  h4.osw { text-transform: uppercase; margin: 0 0 8px; font-size: 17px; }

  .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 22px; text-align: center; }
  @media (min-width: 900px) { .steps { grid-template-columns: repeat(6, 1fr); } }
  .steps .c { width: 64px; height: 64px; border-radius: 50%; background: var(--copper); color: #fff4e3; font-family: 'Oswald'; font-weight: 700; font-size: 28px; display: grid; place-items: center; margin: 0 auto 10px; }
  .steps h4 { font-family: 'Oswald'; font-weight: 700; color: #fff; letter-spacing: 1.5px; text-transform: uppercase; font-size: 14px; margin: 0 0 6px; }
  .steps p { font-style: italic; font-size: 15px; margin: 0; }
  .contact { margin-top: 40px; background: var(--card); border: 1px solid #6b5a4a; border-radius: 8px; padding: 22px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 18px; align-items: center; }
  .contact .slogan { font-family: 'Oswald'; font-weight: 700; color: #fff; letter-spacing: 4px; font-size: 24px; line-height: 1.2; }
  .contact a { color: #fff; }
  footer { text-align: center; font-size: 13px; color: var(--muted); padding: 24px 20px 120px; }

  .sticky { position: fixed; left: 0; right: 0; bottom: 0; z-index: 40; background: #1a1410f2; border-top: 1px solid #ffffff22; padding: 10px 14px calc(10px + env(safe-area-inset-bottom)); display: flex; gap: 10px; align-items: center; color: var(--tan); }
  .sticky .t { flex: 1; font-family: 'Oswald'; line-height: 1.1; font-size: 13px; }
  .sticky .t b { display: block; color: #fff; font-size: 22px; }
  .sticky .btn { padding: 11px 14px; font-size: 14px; }
  @media (min-width: 900px) { .sticky { display: none; } footer { padding-bottom: 32px; } }

  .watermark { position: fixed; inset: 0; z-index: 30; pointer-events: none; overflow: hidden; }
  .watermark div { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%) rotate(-28deg); font-family: 'Oswald'; font-weight: 700; font-size: clamp(56px, 15vw, 190px); color: #8b2e2e; opacity: .16; white-space: nowrap; letter-spacing: 6px; border: 10px solid #8b2e2e; padding: 0 36px; border-radius: 18px; }

  .modal { position: fixed; inset: 0; z-index: 70; background: #1a1410b3; display: none; align-items: flex-end; justify-content: center; }
  .modal.open { display: flex; }
  .sheet { background: var(--cream); width: 100%; max-width: 520px; border-radius: 16px 16px 0 0; padding: 24px 24px calc(24px + env(safe-area-inset-bottom)); max-height: 92vh; overflow-y: auto; }
  @media (min-width: 640px) { .modal { align-items: center; } .sheet { border-radius: 14px; } }
  .sheet h3 { font-family: 'Oswald'; font-weight: 700; text-transform: uppercase; font-size: 24px; margin: 0 0 6px; }
  .sheet label.f { display: block; font-family: 'Oswald'; font-size: 14px; letter-spacing: .5px; margin: 14px 0 6px; text-transform: uppercase; }
  .sheet input[type=text], .sheet textarea, .sheet select { width: 100%; font: inherit; font-size: 16px; padding: 11px 12px; border: 1px solid #cfc4b4; border-radius: 6px; background: #fff; color: var(--ink); }
  .sheet .chk { display: flex; gap: 10px; align-items: flex-start; font-size: 15px; margin-top: 14px; }
  .sheet .chk input { margin-top: 4px; width: 18px; height: 18px; flex: none; }
  .sheet .row { display: flex; gap: 10px; margin-top: 20px; }
  .sheet .row .btn { flex: 1; }
  .sheet .btn-reject { color: var(--ink); }
  .recap { background: var(--sand); border-radius: 8px; padding: 12px 14px; font-size: 15px; margin-top: 8px; }
  .err { color: #b42318; font-size: 14px; margin-top: 6px; }

  @media (max-width: 700px) {
    .ctx .photo { position: static; width: auto; height: 240px; margin: -56px 0 28px; background-position: 70% center; }
  }
</style>
</head>
<body>

@if ($isTeam)
    <div class="team">Vista del equipo: tus visitas no cuentan como vistas del cliente y los botones de respuesta están desactivados.</div>
@endif

@if ($expired)
    <div class="watermark" aria-hidden="true"><div>SIN VIGENCIA</div></div>
@endif

<header class="top">
    <div class="wrap">
        <img src="{{ $assets }}/logo.png" alt="{{ $company }}">
        <div class="meta">COTIZACIÓN {{ $q->number }}<br><a class="btn btn-ghost" href="{{ route('public.quote.pdf', $q->public_token) }}">Descargar PDF</a></div>
    </div>
</header>

@if (session('responded') === 'aceptada' || ($q->status === $S::Accepted && $q->client_response === 'aceptada'))
    <div class="banner ok">✔ Cotización aceptada el {{ fecha($q->client_responded_at) }} por {{ $q->client_responded_by }}. Te contactaremos para el anticipo y la agenda.@if ($waAccepted) <a href="{{ $waAccepted }}">Escribir por WhatsApp</a>@endif</div>
@elseif ($q->status === $S::Accepted)
    <div class="banner ok">✔ Cotización aceptada. Gracias por tu confianza.</div>
@elseif ($q->status === $S::Rejected)
    <div class="banner no">Cotización rechazada{{ $q->client_responded_at ? ' el '.fecha($q->client_responded_at) : '' }}. Gracias por considerarnos; si cambian tus planes, escríbenos.</div>
@elseif ($q->status === $S::Replaced)
    <div class="banner no">Esta cotización fue reemplazada por una versión más reciente.@if ($replacement) <a href="{{ $replacement->publicUrl() }}">Ver {{ $replacement->number }}</a>@endif</div>
@elseif ($expired)
    <div class="banner exp">La vigencia de esta cotización terminó el {{ fecha($q->valid_until) }}.@if ($waUpdate) <a href="{{ $waUpdate }}">Solicitar actualización</a>@endif</div>
@endif
@error('quote')<div class="banner exp">{{ $message }}</div>@enderror

<section class="hero">
    <div class="wrap">
        <div class="tag">Decisiones basadas en datos reales · Preparada para {{ $client->name }}</div>
        <h1>Levantamiento y análisis de fauna mediante dron <span>térmico</span></h1>
        <div class="bar"></div>
        <div class="sub">Inteligencia aérea para manejo cinegético</div>
        @if ($q->valid_until)
            @if ($expired)
                <div class="validity exp">● Sin vigencia · su vigencia terminó el {{ fecha($q->valid_until) }}</div>
            @elseif ($canRespond)
                <div class="validity">● Vigente hasta el {{ fecha($q->valid_until) }} · {{ $daysLeft === 0 ? 'último día' : ($daysLeft === 1 ? 'queda 1 día hábil' : "quedan {$daysLeft} días hábiles") }}</div>
            @else
                <div class="validity neutral">● Emitida el {{ fecha($q->issued_at) }} · vigencia hasta el {{ fecha($q->valid_until) }}</div>
            @endif
        @endif
        <div class="facts">
            <div><b>{{ $ranch->name }}</b><small>Rancho</small></div>
            <div><b>{{ $location }}</b><small>Ubicación</small></div>
            <div><b>{{ $haText }} ha</b><small>Superficie a censar</small></div>
            <div><b>{{ $q->tentative_service_date ? fecha($q->tentative_service_date) : 'Por confirmar' }}</b><small>Fecha tentativa</small></div>
        </div>
    </div>
</section>

<section class="summary" id="inversion">
    <div class="wrap">
        <div class="sumcard">
            <div class="kicker" style="color: var(--tan)">Inversión · {{ $modality }}</div>
            <h3>CENSO TÉRMICO {{ $complete ? 'INTEGRAL ' : '' }}CON AUDITORÍA DIGITAL</h3>
            @if ($scope)<div class="scope">{{ implode(' · ', $scope) }}</div>@endif
            <div class="cols">
                @foreach ($columns as [$label, $value, $unit])
                    <div><small>{{ $label }}</small><b>{{ $value }}</b>@if ($unit)<small>{{ $unit }}</small>@endif</div>
                @endforeach
            </div>
            <div class="total">
                <span class="l">TOTAL</span>
                <span class="v">{{ $pesos($q->total) }} <small>MXN {{ $q->apply_vat ? 'IVA incluido' : '· no incluye IVA' }}</small></span>
                @if ($q->apply_vat)<span class="bk">Subtotal {{ $pesos($q->subtotal) }} + IVA {{ $vatPct }}% {{ $pesos($q->vat_amount) }}</span>@endif
            </div>
            @if ($terms->isNotEmpty())
                <div class="terms">@foreach ($terms as $t)<span>{{ $t }}</span>@endforeach</div>
            @endif
            @include('public.partials.quote-actions')
            @if ($showActions && $waQuestion)
                <div class="hint">¿Dudas antes de decidir? Escríbenos por <a href="{{ $waQuestion }}">WhatsApp</a>.</div>
            @endif
        </div>
    </div>
</section>

<section class="ctx">
    <div class="photo"></div>
    <div class="wrap">
        <div class="kicker">{{ $company }}</div>
        <h2><span class="r">Contexto</span> del predio</h2>
        <div class="stats">
            <div><small>Superficie</small><b>{{ $haText }} ha</b></div>
            <div><small>Ubicación</small><b>{{ $location }}</b></div>
            @if ($fence)<div><small>Tipo de cerca</small><b>{{ $fence }}</b></div>@endif
        </div>
        <div class="box" style="margin-top: 28px"><h4>Objetivo del levantamiento</h4><p>Estimar la población, estructura y distribución de fauna dentro del predio.</p></div>
        @if ($complete)
            <div class="box"><h4>Recomendación de Espectro</h4><p>Realizar un levantamiento integral sobre las {{ $haText }} ha, aprovechando una sola ventana térmica y evitando estimaciones por muestreo.</p></div>
        @endif
    </div>
</section>

@if ($complete)
<section class="dark">
    <div class="wrap">
        <div class="kicker">Modalidad</div>
        <h2>Levantamiento <span class="r">integral</span></h2>
        <div class="stat4">
            <div><b>95 - 98%</b><span>Cobertura objetivo, sujeta a condiciones operativas.</span></div>
            <div><b>VERIFICABLE</b><span>Rutas georreferenciadas para verificar la cobertura efectiva del predio.</span></div>
            <div><b>1 NOCHE</b><span>Levantamiento diseñado para aprovechar la ventana térmica disponible.</span></div>
            <div><b>GPS</b><span>Cada detección georreferenciada dentro del predio.</span></div>
        </div>
        <p>El levantamiento se realiza mediante transectos planificados para recorrer sistemáticamente la superficie seleccionada, aprovechando las condiciones de mayor contraste térmico. Las rutas de vuelo permiten documentar la cobertura efectiva del predio y respaldar el análisis de las detecciones registradas.</p>
        <p>La superficie alcanzable depende de factores como temperatura ambiente, calor acumulado en el terreno, época del año, vegetación y relieve. La prioridad es mantener condiciones adecuadas de detección y una cobertura sistemática, sin comprometer la calidad del levantamiento por alcanzar una superficie determinada.</p>
        <p style="font-style: italic; font-size: 14px; color: var(--muted)">*La cobertura indicada es un objetivo operativo, no una garantía. La superficie efectivamente levantada y la capacidad de detección dependen de las condiciones ambientales, vegetación, relieve y comportamiento de la fauna. Los resultados corresponden a los ejemplares detectados y validados durante el censo.</p>
    </div>
</section>
@endif

<section>
    <div class="wrap">
        <div class="kicker">{{ $company }}</div>
        <h2><span class="r">Metodología</span> y protocolo</h2>
        <div class="grid3">
            @foreach ([
                ['Planeación', 'Diseño de rutas, altura y separación de transectos según vegetación, relieve y características del predio.'],
                ['Levantamiento', 'Barrido térmico sistemático mediante rutas planificadas, aprovechando las condiciones de mayor contraste térmico.'],
                ['Detección y validación', 'Identificación de fauna mediante imagen térmica y confirmación visual cuando las condiciones lo permiten.'],
                ['Bitácora digital', 'Registro georreferenciado de detecciones, incluyendo especie, sexo y clasificación disponible.'],
                ['Auditoría de detecciones', 'Revisión digital de registros para identificar posibles duplicidades, verificar clasificaciones y consolidar el conteo.'],
                ['Análisis y reporte', 'Procesamiento de datos para generar densidades, estructura poblacional, mapas y recomendaciones de manejo.'],
            ] as $i => [$t, $d])
                <div class="item"><span class="n">{{ sprintf('%02d', $i + 1) }}</span><div><h4>{{ $t }}</h4><p>{{ $d }}</p></div></div>
            @endforeach
        </div>
        <div class="note"><b>CADA DETECCIÓN SE REGISTRA, REVISA Y ANALIZA ANTES DE INTEGRARSE AL REPORTE.</b> Registro estructurado, control de duplicidades y trazabilidad entre detecciones, ubicaciones y evidencia.</div>
        <img src="{{ $assets }}/metodologia-fotos.jpg" alt="Rutas de vuelo y equipo en campo" loading="lazy" style="display: block; max-width: 260px; width: 60%; margin: 28px auto 0; border-radius: 6px">
    </div>
</section>

<section style="background: var(--sand)">
    <div class="wrap">
        <div class="kicker">{{ $company }}</div>
        <h2><span class="r">Tecnología</span> propia para el <span class="r">registro y auditoría</span> de detecciones</h2>
        <div class="tech">
            <div><img src="{{ $assets }}/bitacora.jpg" alt="Bitácora digital" loading="lazy"><h4>BITÁCORA DIGITAL</h4><p>Registro digital de cada detección, integrando especie, sexo, ubicación y trazabilidad durante el levantamiento.</p></div>
            <div><img src="{{ $assets }}/auditoria.jpg" alt="Auditoría digital" loading="lazy"><h4>AUDITORÍA DIGITAL</h4><p>Análisis espacial y temporal de las detecciones para identificar posibles duplicidades, revisar registros y reducir errores de conteo.</p></div>
        </div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="kicker">Entregables</div>
        <h2>Lo que recibes al final del <span class="r">levantamiento</span></h2>
        <div class="grid3">
            @foreach ([
                ['Conteo por especie', 'Inventario de ejemplares detectados y validados, clasificados por especie, sexo y edad cuando las condiciones lo permiten.'],
                ['Mapa georreferenciado', 'Distribución espacial de las detecciones para identificar zonas de concentración y presencia de fauna.'],
                ['Análisis de densidades', 'Cálculo de densidades por especie y superficie censada, con indicadores de abundancia poblacional.'],
                ['Estructura poblacional', 'Análisis de la relación macho-hembra, presencia de crías y composición de las poblaciones registradas.'],
                ['Dashboard de resultados', 'Visualización de densidades, estructura poblacional y distribución de fauna mediante gráficas e indicadores.'],
                ['Reporte técnico', 'Resultados consolidados, metodología, mapas, evidencia visual, limitaciones y recomendaciones de manejo.'],
            ] as $i => [$t, $d])
                <div class="item"><span class="n">{{ sprintf('%02d', $i + 1) }}</span><div><h4>{{ $t }}</h4><p>{{ $d }}</p></div></div>
            @endforeach
        </div>
        <div class="note"><b>ENTREGABLE FINAL:</b> Reporte técnico + dashboard de resultados + mapa interactivo + tabla de detecciones + evidencia térmica y RGB.</div>
    </div>
</section>

<section style="background: var(--sand)" id="desglose">
    <div class="wrap">
        <div class="kicker">{{ $q->number }} · Emitida el {{ fecha($q->issued_at) }}</div>
        <h2>Desglose de la <span class="r">inversión</span></h2>
        <div class="two">
            <table class="bd">
                <tr><td>Servicio de censo térmico<small>{{ $haText }} ha</small></td><td>{{ money($q->service_amount) }}</td></tr>
                <tr><td>Traslado y movilización</td><td>{{ money($q->logistics_amount) }}</td></tr>
                @if ((float) $q->travel_expenses > 0)<tr><td>Viáticos</td><td>{{ money($q->travel_expenses) }}</td></tr>@endif
                @if ($q->hasDiscount())<tr class="disc"><td>{{ $q->discountLabel() }}</td><td>−{{ money($q->discount_amount) }}</td></tr>@endif
                <tr><td>Subtotal</td><td>{{ money($q->subtotal) }}</td></tr>
                <tr><td>{{ $q->apply_vat ? 'IVA '.$vatPct.'%' : 'IVA' }}</td><td>{{ $q->apply_vat ? money($q->vat_amount) : 'No aplica' }}</td></tr>
                <tr class="tot"><td>Total MXN</td><td>{{ money($q->total) }}</td></tr>
            </table>
            <div>
                @if ($scope)
                    <h4 class="osw">Incluye</h4>
                    <ul class="incl">@foreach ($scope as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif
                @if ($terms->isNotEmpty())
                    <h4 class="osw" style="margin-top: 18px">Condiciones de pago</h4>
                    <ul class="incl">@foreach ($terms as $t)<li>{{ $t }}</li>@endforeach</ul>
                @endif
                @if ($separateCharges)<p style="font-size: 14px; color: var(--muted)">{{ $separateCharges }}</p>@endif
                @if ($q->observations)<p style="font-size: 15px"><b>Observaciones:</b> {{ $q->observations }}</p>@endif
                <p style="font-size: 14px; color: var(--muted)">Vigencia: hasta el {{ fecha($q->valid_until) }} (días hábiles a partir de la emisión).</p>
            </div>
        </div>
    </div>
</section>

<section class="dark">
    <div class="wrap">
        <div class="kicker">Siguientes pasos</div>
        <h2>Del acuerdo al reporte en <span class="w">6 pasos</span></h2>
        <div class="steps">
            @foreach ([
                ['Confirmación', 'Definimos el alcance, la superficie y las condiciones del servicio.'],
                ['Anticipo y agenda', 'Reservamos la fecha del levantamiento mediante un anticipo del 25%.'],
                ['Planeación', 'Validamos el perímetro y diseñamos las rutas de vuelo considerando vegetación, relieve y características del predio.'],
                ['Levantamiento', 'Realizamos el barrido térmico sistemático y registramos las detecciones en la bitácora digital.'],
                ['Auditoría y análisis', 'Revisamos posibles duplicidades, validamos los registros y consolidamos los indicadores poblacionales.'],
                ['Resultados', 'Entregamos el reporte técnico, mapas, dashboard de resultados y evidencia visual.'],
            ] as $i => [$t, $d])
                <div><div class="c">{{ $i + 1 }}</div><h4>{{ $t }}</h4><p>{{ $d }}</p></div>
            @endforeach
        </div>
        <div style="max-width: 560px; margin: 40px auto 0">@include('public.partials.quote-actions')</div>
        <div class="contact">
            <div><div class="slogan">TU RANCHO VALE MÁS<br>CON DATOS.</div><i style="font-size: 14px">{{ $company }} · Inteligencia Aérea Cinegética</i></div>
            <div style="font-family: Oswald; line-height: 2">
                @if ($waQuestion)<a class="btn btn-accept" href="{{ $waQuestion }}" style="padding: 10px 16px; color: var(--ink)">WhatsApp {{ $business['whatsapp'] }}</a><br>@endif
                @if (! empty($business['email']))<a href="mailto:{{ $business['email'] }}">{{ $business['email'] }}</a><br>@endif
                @if (! empty($business['website']))<a href="https://{{ preg_replace('#^https?://#', '', $business['website']) }}" rel="noopener">{{ $business['website'] }}</a>@endif
            </div>
        </div>
    </div>
</section>
<footer>{{ $company }} · Cotización {{ $q->number }}. Este enlace es personal; compártelo solo con quien decide la contratación.</footer>

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
            <div class="recap">{{ $q->number }} · {{ $ranch->name }} · {{ $haText }} ha<br><b class="osw" style="font-size: 20px">{{ $pesos($q->total) }} MXN {{ $q->apply_vat ? 'IVA incluido' : 'sin IVA' }}</b></div>
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
