{{--
    PDF de cotización con el diseño de la propuesta COT-159 (Canva, 1440 × 810).
    Láminas de 960 × 540 pt: cada coordenada es la del original × 2/3.
    Fuentes Oswald y Source Serif con métricas verticales normalizadas (ver QuotePdf::FONT_VERSION).
    Solo usa importes guardados en la cotización. No muestra notas internas, motivos de
    ajustes o descuentos ni el detalle del cálculo progresivo.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }} · {{ $business['name'] ?? 'Espectro Soluciones' }}</title>
<style>
    @font-face { font-family: 'Oswald'; font-weight: normal; font-style: normal; src: url('{{ $fonts }}/Oswald-Regular.ttf') format('truetype'); }
    @font-face { font-family: 'Oswald'; font-weight: bold; font-style: normal; src: url('{{ $fonts }}/Oswald-Bold.ttf') format('truetype'); }
    @font-face { font-family: 'OswaldLight'; font-weight: normal; font-style: normal; src: url('{{ $fonts }}/Oswald-Light.ttf') format('truetype'); }
    @font-face { font-family: 'Serif'; font-weight: normal; font-style: normal; src: url('{{ $fonts }}/SourceSerif4-Regular.ttf') format('truetype'); }
    @font-face { font-family: 'Serif'; font-weight: normal; font-style: italic; src: url('{{ $fonts }}/SourceSerif4-It.ttf') format('truetype'); }
    @font-face { font-family: 'Serif'; font-weight: bold; font-style: normal; src: url('{{ $fonts }}/SourceSerif4-Bold.ttf') format('truetype'); }

    @page { margin: 0; }
    body { margin: 0; font-family: 'Serif'; font-size: 12pt; line-height: 1.2; }
    table { border-collapse: collapse; }
    td { padding: 0; vertical-align: middle; }

    .slide { position: relative; width: 960pt; height: 540pt; overflow: hidden; page-break-after: always; }
    .slide.last { page-break-after: auto; }
    .dark { background: #1a1410; color: #d6c2a1; }
    .light { background: #fdfaf6; color: #1a1410; }
    .cream { background: #f2ede6; color: #1a1410; }

    .a { position: absolute; }
    .osw { font-family: 'Oswald'; }
    .oswb { font-family: 'Oswald'; font-weight: bold; }
    .oswl { font-family: 'OswaldLight'; }
    .it { font-style: italic; }
    .up { text-transform: uppercase; }
    .c { text-align: center; }
    .r { text-align: right; }
    .red { color: #8b2e2e; }
    .tan { color: #d6c2a1; }
    .white { color: #ffffff; }
    .ink { color: #1a1410; }

    .kicker { position: absolute; left: 20pt; top: 24pt; font-family: 'Oswald'; font-size: 11pt; line-height: 1; letter-spacing: 1.6pt; text-transform: uppercase; color: #8f8378; }
    .h1 { position: absolute; font-family: 'Oswald'; font-weight: bold; text-transform: uppercase; line-height: 1; letter-spacing: -0.6pt; }
    .card { position: absolute; background: #2b231f; border-radius: 3pt; }
    .vl { position: absolute; width: 1.5pt; background: #1a1410; }
    .hl { position: absolute; height: 1.5pt; background: #1a1410; }
</style>
</head>
<body>
@php
    $complete = $quote->opportunity->service_type === \App\Enums\ServiceType::Complete;
    $modality = $complete ? 'Levantamiento integral' : 'Censo '.mb_strtolower($quote->opportunity->service_type->label());
    [$modalityA, $modalityB] = array_pad(explode(' ', mb_strtoupper($modality), 2), 2, '');
    $ha = (float) $quote->hectares;
    $haText = number_format($ha, fmod($ha, 1.0) == 0.0 ? 0 : 2);
    $location = $ranch->location() ?: 'Por confirmar';
    $fence = $ranch->fence_type?->label();
    $pesos = fn ($v) => '$'.number_format((float) $v, fmod(round((float) $v, 2), 1.0) == 0.0 ? 0 : 2);
    $vatPct = rtrim(rtrim(number_format((float) $quote->vat_rate, 2), '0'), '.');
    $columns = array_values(array_filter([
        ['Superficie', $haText, 'Hectáreas'],
        ['Servicio', $pesos($quote->service_amount), null],
        ['Logística y movilización', $pesos($quote->logistics_amount), null],
        (float) $quote->travel_expenses > 0 ? ['Viáticos', $pesos($quote->travel_expenses), null] : null,
        $quote->hasDiscount() ? [$quote->discountLabel(), '−'.$pesos($quote->discount_amount), null] : null,
    ]));
    $terms = collect(preg_split('/\R/', $paymentTerms))->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values();
    $company = $business['name'] ?? 'Espectro Soluciones';
    $notes = collect([
        $quote->observations ? 'Observaciones: '.$quote->observations : null,
        $separateCharges ?: null,
    ])->filter()->implode('  ·  ');
@endphp

{{-- 1. Portada --}}
<div class="slide dark">
    <img class="a" src="{{ $logo }}" style="left: 22pt; top: 16pt; width: 88pt;" alt="">
    <div class="a osw r" style="right: 33pt; top: 26pt; font-size: 14pt; line-height: 1;">PROPUESTA DE SERVICIOS</div>
    <div class="a osw r" style="right: 33pt; top: 46pt; font-size: 9.5pt; line-height: 1; letter-spacing: 1pt; color: #8f8378;">COTIZACIÓN {{ $quote->number }} · {{ fecha($quote->issued_at) }}</div>

    <div class="a it" style="left: 54pt; top: 141pt; font-size: 11pt; line-height: 1;">Decisiones basadas en datos reales</div>
    <div class="a oswb white" style="left: 54pt; top: 160pt; font-size: 54pt; line-height: 1.0; letter-spacing: 0.2pt;">LEVANTAMIENTO Y ANÁLISIS<br>DE FAUNA MEDIANTE DRON<br><span class="red">TÉRMICO</span></div>
    <div class="a" style="left: 54pt; top: 337pt; width: 91pt; height: 3pt; background: #d6c2a1;"></div>
    <div class="a osw" style="left: 54pt; top: 349pt; font-size: 16.5pt; line-height: 1; letter-spacing: 6.4pt;">INTELIGENCIA AÉREA PARA MANEJO CINEGÉTICO</div>

    <div class="a" style="left: 25pt; top: 422pt; width: 899pt; height: 0.8pt; background: #8a7a62;"></div>
    <div class="a oswb" style="left: 34pt; top: 463pt; font-size: 16pt; line-height: 1; letter-spacing: 2pt;">{{ $location }}</div>
    <div class="a osw" style="left: 34pt; top: 488pt; font-size: 14pt; line-height: 1; letter-spacing: 0.6pt;">UBICACIÓN</div>
    <div class="a it" style="left: 34pt; top: 511pt; font-size: 9.5pt; line-height: 1; color: #8f8378;">{{ $ranch->name }} · Preparada para {{ $client->name }}</div>

    <div class="a oswb c" style="left: 808pt; top: 434pt; width: 150pt; font-size: 39pt; line-height: 1;">{{ $haText }}</div>
    <div class="a oswb c" style="left: 808pt; top: 476pt; width: 150pt; font-size: 15pt; line-height: 1; letter-spacing: 3pt;">HECTÁREAS</div>
    <div class="a osw r" style="right: 10pt; top: 509pt; font-size: 13pt; line-height: 1; letter-spacing: 2pt;">SUPERFICIE DEL PREDIO</div>
</div>

{{-- 2. Contexto del predio --}}
<div class="slide light">
    <img class="a" src="{{ $images['venado'] }}" style="left: 0; top: 0; width: 960pt; height: 540pt;" alt="">
    <div class="kicker">{{ $company }}</div>
    <div class="h1 ink" style="left: 21pt; top: 63pt; font-size: 45pt;"><span class="red">Contexto</span> del predio</div>

    <img class="a" src="{{ $icons['superficie'] }}" style="left: 19pt; top: 163pt; width: 68pt;" alt="">
    <div class="a osw" style="left: 90pt; top: 158pt; font-size: 15pt; line-height: 1;">SUPERFICIE</div>
    <div class="a oswb c" style="left: 92pt; top: {{ mb_strlen($haText) > 3 ? '186pt' : '180pt' }}; width: 100pt; font-size: {{ mb_strlen($haText) > 5 ? '26pt' : (mb_strlen($haText) > 3 ? '32pt' : '41pt') }}; line-height: 1;">{{ $haText }}</div>
    <div class="a osw c" style="left: 92pt; top: 226pt; width: 100pt; font-size: 11pt; line-height: 1; letter-spacing: 3pt;">HECTÁREAS</div>

    <div class="vl" style="left: 202pt; top: 149pt; height: 104pt; width: 2pt;"></div>
    <img class="a" src="{{ $icons['ubicacion'] }}" style="left: 223pt; top: 175pt; width: 48pt;" alt="">
    <div class="a osw" style="left: 272pt; top: 166pt; font-size: 15pt; line-height: 1;">UBICACIÓN</div>
    <div class="a oswb" style="left: 281pt; top: {{ mb_strlen($location) > 16 ? '196pt' : '205pt' }}; width: {{ $fence ? '146pt' : '330pt' }}; font-size: {{ mb_strlen($location) > 16 ? '17pt' : '21pt' }}; line-height: 1.1;">{{ $location }}</div>

    @if ($fence)
        <div class="vl" style="left: 432pt; top: 149pt; height: 104pt; width: 2pt;"></div>
        <img class="a" src="{{ $icons['cerca'] }}" style="left: 440pt; top: 166pt; width: 67pt;" alt="">
        <div class="a osw" style="left: 514pt; top: 166pt; font-size: 15pt; line-height: 1;">TIPO DE CERCA</div>
        <div class="a oswb up" style="left: 516pt; top: 201pt; font-size: 21pt; line-height: 1;">{{ $fence }}</div>
    @endif

    <div class="card" style="left: 21pt; top: 274pt; width: 595pt; height: 94pt;"></div>
    <img class="a" src="{{ $icons['objetivo'] }}" style="left: 36pt; top: 288pt; width: 66pt;" alt="">
    <div class="a" style="left: 113pt; top: 291pt; width: 2pt; height: 66pt; background: #f1ece6;"></div>
    <div class="a oswb" style="left: 147pt; top: 291pt; font-size: 16pt; line-height: 1; color: #f1ece6;">OBJETIVO DEL LEVANTAMIENTO</div>
    <div class="a oswl" style="left: 147pt; top: 316pt; width: 385pt; font-size: 16pt; line-height: 1.15; color: #f1ece6;">Estimar la población, estructura y distribución de fauna dentro del predio.</div>

    @if ($complete)
        <div class="card" style="left: 21pt; top: 388pt; width: 595pt; height: 94pt;"></div>
        <img class="a" src="{{ $icons['recomendacion'] }}" style="left: 39pt; top: 399pt; width: 65pt;" alt="">
        <div class="a" style="left: 113pt; top: 404pt; width: 2pt; height: 66pt; background: #f1ece6;"></div>
        <div class="a oswb" style="left: 147pt; top: 404pt; font-size: 16pt; line-height: 1; color: #f1ece6;">RECOMENDACIÓN DE ESPECTRO</div>
        <div class="a oswl" style="left: 147pt; top: 429pt; width: 410pt; font-size: 16pt; line-height: 1.15; color: #f1ece6;">Realizar un levantamiento integral sobre las {{ $haText }} ha, aprovechando una sola ventana térmica y evitando estimaciones por muestreo.</div>
    @endif
</div>

{{-- 3. Modalidad (texto de COT-159; solo existe para el levantamiento integral) --}}
@if ($complete)
<div class="slide dark">
    <div class="kicker" style="color: #9a8b7b;">Levantamiento y análisis de fauna mediante dron térmico</div>
    <div class="a osw" style="left: 52pt; top: 74pt; font-size: 11.5pt; line-height: 1; letter-spacing: 1pt; color: #9a8b7b;">MODALIDAD</div>
    <div class="h1" style="left: 52pt; top: 92pt; font-size: 38pt;"><span class="tan">LEVANTAMIENTO</span> <span class="red">INTEGRAL</span></div>
    <div class="a oswl white" style="left: 52pt; top: 136pt; font-size: 11.5pt; line-height: 1; letter-spacing: 0.9pt;">COBERTURA DEFINIDA POR LAS CONDICIONES DEL LEVANTAMIENTO</div>

    <div class="a" style="left: 52pt; top: 164pt; width: 425pt; font-size: 12pt; line-height: 1.25;">
        El levantamiento se realiza mediante transectos planificados para recorrer sistemáticamente la superficie seleccionada, aprovechando las condiciones de mayor contraste térmico.
        <div style="margin-top: 16pt;">Las rutas de vuelo permiten documentar la cobertura efectiva del predio y respaldar el análisis de las detecciones registradas.</div>
        <div style="margin-top: 16pt;">La superficie alcanzable depende de factores como:</div>
        <div style="margin-left: 10pt; font-weight: bold; line-height: 1.2;">•&nbsp; Temperatura ambiente<br>•&nbsp; Calor acumulado en el terreno<br>•&nbsp; Época del año<br>•&nbsp; Vegetación y relieve</div>
    </div>

    <div class="a" style="left: 54pt; top: 401pt; width: 431pt; height: 90pt; background: #2b231f; border: 0.8pt solid #6b5a4a; border-radius: 4pt;"></div>
    <div class="a osw white" style="left: 79pt; top: 411pt; font-size: 14pt; line-height: 1; letter-spacing: 1.4pt;">CONSIDERACIÓN IMPORTANTE</div>
    <div class="a it" style="left: 79pt; top: 432pt; width: 395pt; font-size: 12pt; line-height: 1.3;">La prioridad es mantener condiciones adecuadas de detección y una cobertura sistemática, sin comprometer la calidad del levantamiento por alcanzar una superficie determinada.</div>

    <div class="a oswb c" style="left: 525pt; top: 72pt; width: 160pt; font-size: 33pt; line-height: 1; letter-spacing: -1pt;">95 - 98%</div>
    <div class="a it c" style="left: 525pt; top: 122pt; width: 160pt; font-size: 10.5pt; line-height: 1.25;">Cobertura objetivo, sujeta<br>a condiciones operativas.</div>
    <div class="a oswb c" style="left: 716pt; top: 63pt; width: 180pt; font-size: 26pt; line-height: 0.85; letter-spacing: -1pt;">COBERTURA<br>VERIFICABLE</div>
    <div class="a it c" style="left: 716pt; top: 131pt; width: 180pt; font-size: 10.5pt; line-height: 1.25;">Rutas georreferenciadas para verificar la cobertura efectiva del predio.</div>
    <div class="a oswb c" style="left: 525pt; top: 196pt; width: 160pt; font-size: 33pt; line-height: 1; letter-spacing: -1pt;">1 NOCHE</div>
    <div class="a it c" style="left: 525pt; top: 235pt; width: 160pt; font-size: 10.5pt; line-height: 1.25;">Levantamiento diseñado para aprovechar la ventana térmica disponible.</div>
    <div class="a oswb c" style="left: 716pt; top: 196pt; width: 180pt; font-size: 33pt; line-height: 1; letter-spacing: -1pt;">GPS</div>
    <div class="a it c" style="left: 716pt; top: 243pt; width: 180pt; font-size: 10.5pt; line-height: 1.25;">Cada detección georreferenciada<br>dentro del predio</div>

    <table class="a" style="left: 511pt; top: 312pt; width: 423pt; border: 0.8pt solid #5c3d22;">
        <tr style="background: #5c3d22; height: 45pt;">
            <td class="oswb c" style="width: 106pt; height: 45pt; font-size: 13pt; color: #f6ead6;">CONDICIÓN</td>
            <td class="oswb c" style="width: 134pt; font-size: 13pt; color: #f6ead6;">DISEÑO</td>
            <td class="oswb c" style="font-size: 13pt; color: #f6ead6;">RESULTADO</td>
        </tr>
        <tr>
            <td class="osw c" style="height: 61pt; font-size: 12pt; line-height: 1.25; border: 0.8pt solid #5c3d22;">CONDICIONES<br>FAVORABLES</td>
            <td class="c" style="font-size: 12.5pt; line-height: 1.35; border: 0.8pt solid #5c3d22; padding: 0 8pt;">Mayor ventana operativa</td>
            <td class="c" style="font-size: 12.5pt; line-height: 1.35; border: 0.8pt solid #5c3d22; padding: 0 8pt;">Mayor cobertura</td>
        </tr>
        <tr>
            <td class="osw c" style="height: 56pt; font-size: 12pt; line-height: 1.25; border: 0.8pt solid #5c3d22;">CONDICIONES<br>LIMITANTES</td>
            <td class="c" style="font-size: 12.5pt; line-height: 1.35; border: 0.8pt solid #5c3d22; padding: 0 8pt;">Ajuste de horario y alcance</td>
            <td class="c" style="font-size: 12.5pt; line-height: 1.35; border: 0.8pt solid #5c3d22; padding: 0 8pt;">Se prioriza la calidad de detección.</td>
        </tr>
    </table>

    <div class="a it" style="left: 52pt; top: 499pt; width: 880pt; font-size: 9.5pt; line-height: 1.3;">*La cobertura indicada es un objetivo operativo, no una garantía. La superficie efectivamente levantada y la capacidad de detección dependen de las condiciones ambientales, vegetación, relieve y comportamiento de la fauna. Los resultados corresponden a los ejemplares detectados y validados durante el censo.</div>
</div>
@endif

{{-- 4. Metodología y protocolo --}}
@php
    $method = [
        // [número x, título x, ícono, top fila, ancho texto, número, título, texto]
        [35, 76, 'm1', 157, 133, '01', 'Planeación', 'Diseño de rutas, altura y separación de transectos según vegetación, relieve y características del predio.'],
        [258, 301, 'm2', 157, 188, '02', 'Levantamiento', 'Barrido térmico sistemático mediante rutas planificadas, aprovechando las condiciones de mayor contraste térmico.'],
        [521, 565, 'm3', 157, 162, '03', 'Detección y validación', 'Identificación de fauna mediante imagen térmica y confirmación visual cuando las condiciones lo permiten.'],
        [37, 81, 'm4', 274, 133, '04', 'Bitácora digital', 'Registro georreferenciado de detecciones, incluyendo especie, sexo y clasificación disponible.'],
        [259, 303, 'm5', 274, 188, '05', 'Auditoría de detecciones', 'Revisión digital de registros para identificar posibles duplicidades, verificar clasificaciones y consolidar el conteo.'],
        [521, 565, 'm6', 274, 162, '06', 'Análisis y reporte', 'Procesamiento de datos para generar densidades, estructura poblacional, mapas y recomendaciones de manejo.'],
    ];
    $methodIcons = ['m1' => [32, 190, 38], 'm2' => [252, 196, 48], 'm3' => [509, 189, 55], 'm4' => [32, 307, 42], 'm5' => [252, 310, 45], 'm6' => [510, 305, 52]];
@endphp
<div class="slide light">
    <img class="a" src="{{ $images['metodologia'] }}" style="left: 728pt; top: 0; width: 232pt; height: 540pt;" alt="">
    <div class="kicker">{{ $company }}</div>
    <div class="h1 ink" style="left: 31pt; top: 64pt; font-size: 45.5pt;"><span class="red">Metodología</span> y protocolo</div>

    <div class="vl" style="left: 231pt; top: 144pt; height: 104pt;"></div>
    <div class="vl" style="left: 497pt; top: 144pt; height: 104pt;"></div>
    <div class="hl" style="left: 73pt; top: 258pt; width: 664pt;"></div>
    <div class="vl" style="left: 232pt; top: 270pt; height: 105pt;"></div>
    <div class="vl" style="left: 497pt; top: 270pt; height: 105pt;"></div>

    @foreach ($method as [$nx, $tx, $icon, $top, $w, $n, $title, $text])
        <div class="a oswb red" style="left: {{ $nx }}pt; top: {{ $top }}pt; font-size: 31pt; line-height: 1;">{{ $n }}</div>
        @php [$ix, $iy, $iw] = $methodIcons[$icon]; @endphp
        <img class="a" src="{{ $icons[$icon] }}" style="left: {{ $ix }}pt; top: {{ $iy }}pt; width: {{ $iw }}pt;" alt="">
        <div class="a oswb up ink" style="left: {{ $tx }}pt; top: {{ $top - 4 }}pt; font-size: 15.3pt; line-height: 1;">{{ $title }}</div>
        <div class="a osw ink" style="left: {{ $tx }}pt; top: {{ $top + 15 }}pt; width: {{ $w }}pt; font-size: 12.5pt; line-height: 1.25;">{{ $text }}</div>
    @endforeach

    <div class="card" style="left: 21pt; top: 394pt; width: 696pt; height: 137pt;"></div>
    <div class="a osw" style="left: 57pt; top: 408pt; font-size: 18pt; line-height: 1; color: #f1ece6;">CADA DETECCIÓN SE REGISTRA, REVISA Y ANALIZA ANTES DE INTEGRARSE AL REPORTE.</div>
    @foreach ([[68, 'REGISTRO ESTRUCTURADO', 'Bitácora digital con información georreferenciada de las detecciones.'], [300, 'CONTROL DE DUPLICIDADES', 'Herramientas de auditoría para identificar y revisar posibles registros repetidos.'], [543, 'TRAZABILIDAD', 'Relación entre detecciones, ubicaciones y evidencia disponible para su revisión.']] as [$x, $t, $d])
        <div class="a osw" style="left: {{ $x }}pt; top: 443pt; font-size: 16pt; line-height: 1; color: #f1ece6;">{{ $t }}</div>
        <div class="a oswl" style="left: {{ $x }}pt; top: 466pt; width: 168pt; font-size: 13.5pt; line-height: 1.2; color: #f1ece6;">{{ $d }}</div>
    @endforeach
</div>

{{-- 5. Tecnología propia --}}
<div class="slide light">
    <div class="kicker">{{ $company }}</div>
    <div class="h1 ink" style="left: 31pt; top: 62pt; font-size: 40.5pt; line-height: 0.95;"><span class="red">Tecnología</span> propia para el <span class="red">registro y auditoría</span><br>de detecciones</div>

    <img class="a" src="{{ $images['bitacora'] }}" style="left: 91pt; top: 167pt; width: 334pt; height: 244pt;" alt="">
    <div class="a oswb c" style="left: 91pt; top: 418pt; width: 334pt; font-size: 20pt; line-height: 1; color: #8c5a3c;">BITÁCORA DIGITAL</div>
    <div class="a osw ink" style="left: 91pt; top: 452pt; width: 334pt; font-size: 16pt; line-height: 1.2;">Registro digital de cada detección, integrando especie, sexo, ubicación y trazabilidad durante el levantamiento.</div>

    <img class="a" src="{{ $images['auditoria'] }}" style="left: 536pt; top: 167pt; width: 334pt; height: 247pt;" alt="">
    <div class="a oswb c" style="left: 536pt; top: 418pt; width: 334pt; font-size: 20pt; line-height: 1; color: #8c5a3c;">AUDITORÍA DIGITAL</div>
    <div class="a osw ink" style="left: 536pt; top: 450pt; width: 340pt; font-size: 16pt; line-height: 1.2;">Análisis espacial y temporal de las detecciones para identificar posibles duplicidades, revisar registros y reducir errores de conteo.</div>
</div>

{{-- 6. Entregables --}}
@php
    $deliverables = [
        [51, 103, 148, '01', 'Conteo por especie', 'Inventario de ejemplares detectados y validados, clasificados por especie, sexo y edad cuando las condiciones lo permiten.'],
        [338, 389, 148, '02', 'Mapa georreferenciado', 'Distribución espacial de las detecciones para identificar zonas de concentración y presencia de fauna.'],
        [634, 684, 148, '03', 'Análisis de densidades', 'Cálculo de densidades por especie y superficie censada, con indicadores de abundancia poblacional.'],
        [47, 99, 293, '04', 'Estructura poblacional', 'Análisis de la relación macho-hembra, presencia de crías y composición de las poblaciones registradas.'],
        [340, 391, 293, '05', 'Dashboard de resultados', 'Visualización de densidades, estructura poblacional y distribución de fauna mediante gráficas e indicadores.'],
        [636, 690, 293, '06', 'Reporte técnico', 'Resultados consolidados, metodología, mapas, evidencia visual, limitaciones y recomendaciones de manejo.'],
    ];
@endphp
<div class="slide light">
    <div class="kicker">{{ $company }}</div>
    <div class="h1 ink" style="left: 31pt; top: 64pt; font-size: 45.5pt;">Lo que recibes al final del <span class="red">levantamiento</span></div>

    <div class="vl" style="left: 320pt; top: 147pt; height: 104pt;"></div>
    <div class="vl" style="left: 610pt; top: 147pt; height: 104pt;"></div>
    <div class="hl" style="left: 52pt; top: 269pt; width: 868pt;"></div>
    <div class="vl" style="left: 320pt; top: 294pt; height: 105pt;"></div>
    <div class="vl" style="left: 610pt; top: 294pt; height: 105pt;"></div>

    @foreach ($deliverables as [$nx, $tx, $top, $n, $title, $text])
        <div class="a oswb red" style="left: {{ $nx }}pt; top: {{ $top }}pt; font-size: 34pt; line-height: 1;">{{ $n }}</div>
        <div class="a oswb up ink" style="left: {{ $tx }}pt; top: {{ $top - 7 }}pt; font-size: 17.2pt; line-height: 1;">{{ $title }}</div>
        <div class="a osw ink" style="left: {{ $tx }}pt; top: {{ $top + 32 }}pt; width: 208pt; font-size: 15.3pt; line-height: 1.15;">{{ $text }}</div>
    @endforeach

    <div class="card" style="left: 55pt; top: 453pt; width: 865pt; height: 69pt;"></div>
    <img class="a" src="{{ $icons['entregable'] }}" style="left: 79pt; top: 466pt; width: 44pt;" alt="">
    <div class="a" style="left: 129pt; top: 463pt; width: 1.5pt; height: 50pt; background: #f1ece6;"></div>
    <div class="a oswb" style="left: 145pt; top: 479pt; font-size: 16pt; line-height: 1; color: #f1ece6;">ENTREGABLE FINAL:</div>
    <div class="a osw" style="left: 280pt; top: 466pt; width: 580pt; font-size: 16pt; line-height: 1.15; color: #f1ece6;">Reporte técnico + dashboard de resultados + mapa interactivo + tabla de detecciones + evidencia térmica y RGB.</div>
</div>

{{-- 7. Inversión --}}
@php
    $n = count($columns);
    [$colLeft, $colWidth] = $n === 3 ? [3, 951] : [69, 811];
@endphp
<div class="slide cream">
    <div class="kicker">Inversión - {{ $modality }} · {{ $quote->number }}</div>
    <div class="h1 ink" style="left: 54pt; top: 63pt; font-size: 46.5pt; letter-spacing: -1.4pt;"><span class="red">{{ $modalityA }}</span> {{ $modalityB }}.</div>
    <div class="a it" style="left: 54pt; top: 113pt; font-size: 11.5pt; line-height: 1;">Propuesta vigente hasta el {{ fecha($quote->valid_until) }} · Emitida el {{ fecha($quote->issued_at) }}{{ $quote->tentative_service_date ? ' · Fecha tentativa del censo: '.fecha($quote->tentative_service_date) : '' }}</div>
    @if ($notes)
        <div class="a" style="left: 54pt; top: 128pt; width: 851pt; font-size: 8.5pt; line-height: 1; color: #5a4a3c;">{{ \Illuminate\Support\Str::limit($notes, 230) }}</div>
    @endif

    <div class="a" style="left: 55pt; top: 142pt; width: 851pt; height: 331pt; background: #1a1410; border: 0.8pt solid #6b5a4a; border-radius: 4pt;"></div>
    <div class="a oswb tan" style="left: 69pt; top: 166pt; font-size: 21pt; line-height: 1;">CENSO TÉRMICO {{ $complete ? 'INTEGRAL ' : '' }}CON AUDITORÍA DIGITAL</div>
    @if ($scope)
        <div class="a it tan" style="left: 69pt; top: 191pt; font-size: 13pt; line-height: 1;">{{ implode(' · ', $scope) }}</div>
    @endif
    <div class="a" style="left: 69pt; top: 220pt; width: 811pt; height: 0.8pt; background: #8a7a62;"></div>

    <table class="a" style="left: {{ $colLeft }}pt; top: 236pt; width: {{ $colWidth }}pt;">
        <tr>
            @foreach ($columns as [$label, $value, $unit])
                <td class="c" style="width: {{ round($colWidth / $n, 2) }}pt; vertical-align: top;">
                    <div class="osw up tan" style="font-size: {{ $n > 3 ? '14pt' : '16pt' }}; line-height: 1;">{{ $label }}</div>
                    <div class="oswb white" style="margin-top: 18pt; font-size: {{ $n > 3 ? '30pt' : '35pt' }}; line-height: 1;">{{ $value }}</div>
                    @if ($unit)<div class="osw up tan" style="margin-top: 14pt; font-size: 13pt; line-height: 1;">{{ $unit }}</div>@endif
                </td>
            @endforeach
        </tr>
    </table>

    <div class="a" style="left: 68pt; top: 398pt; width: 819pt; height: 61pt; background: #b9914b;"></div>
    <div class="a oswb ink" style="left: 145pt; top: 410pt; font-size: 38pt; line-height: 1;">TOTAL:</div>
    <div class="a ink" style="left: 413pt; top: 410pt; line-height: 1;">
        <span class="oswb" style="font-size: 38pt;">{{ $pesos($quote->total) }}</span>
        <span class="osw" style="font-size: 17pt;">&nbsp;{{ $quote->apply_vat ? 'IVA incluido' : 'No incluye IVA' }}</span>
    </div>
    @if ($quote->apply_vat)
        <div class="a osw ink r" style="right: 86pt; top: 436pt; font-size: 10.5pt; line-height: 1;">Subtotal {{ $pesos($quote->subtotal) }} + IVA {{ $vatPct }}% {{ $pesos($quote->vat_amount) }}</div>
    @endif

    @if ($terms->isNotEmpty())
        <div class="a" style="left: 55pt; top: 493pt; width: 851pt; height: 24pt; background: #fcfbf9; border: 0.8pt solid #d6c2a1; border-radius: 4pt;"></div>
        <table class="a" style="left: 55pt; top: 493pt; width: 851pt; height: 25pt;">
            <tr>
                @foreach ($terms as $term)
                    <td class="c" style="height: 25pt; font-size: 13pt; line-height: 1; color: #7a3e22;">•&nbsp; {{ $term }}</td>
                @endforeach
            </tr>
        </table>
    @endif
</div>

{{-- 8. Siguientes pasos --}}
@php
    $next = [
        ['Confirmación', 'Definimos el alcance, la superficie y las condiciones del servicio.'],
        ['Anticipo y agenda', 'Reservamos la fecha del levantamiento mediante un anticipo del 25%.'],
        ['Planeación', 'Validamos el perímetro y diseñamos las rutas de vuelo considerando vegetación, relieve y características del predio.'],
        ['Levantamiento', 'Realizamos el barrido térmico sistemático y registramos las detecciones en la bitácora digital.'],
        ['Auditoría y análisis', 'Revisamos posibles duplicidades, validamos los registros y consolidamos los indicadores poblacionales.'],
        ['Resultados', 'Entregamos el reporte técnico, mapas, dashboard de resultados y evidencia visual.'],
    ];
    $centers = [91, 244, 397, 550, 702, 855];
    $contact = array_values(array_filter([
        ['WEB', $business['website'] ?? ''],
        ['EMAIL', $business['email'] ?? ''],
        ['WHATSAPP', $business['whatsapp'] ?? ''],
    ], fn ($row) => $row[1] !== ''));
@endphp
<div class="slide dark last">
    <div class="kicker" style="color: #9a8b7b;">Siguientes pasos</div>
    <div class="h1 tan" style="left: 54pt; top: 64pt; font-size: 45pt;">Del acuerdo al reporte en <span class="white">6 pasos</span></div>
    <div class="a it" style="left: 54pt; top: 116pt; font-size: 12pt; line-height: 1;">Del acuerdo inicial a la entrega del reporte técnico.</div>

    <div class="a" style="left: 91pt; top: 198pt; width: 764pt; height: 0.8pt; background: #8a7a62;"></div>
    @foreach ($next as $i => [$title, $text])
        @php $cx = $centers[$i]; @endphp
        <div class="a" style="left: {{ $cx - 37.5 }}pt; top: 160.5pt; width: 75pt; height: 75pt; border-radius: 37.5pt; background: #8a5a3a;"></div>
        <div class="a oswb c" style="left: {{ $cx - 37.5 }}pt; top: 184pt; width: 75pt; font-size: 29pt; line-height: 1; color: #fff4e3;">{{ $i + 1 }}</div>
        <div class="a oswb c white" style="left: {{ $cx - 75 }}pt; top: 257pt; width: 150pt; font-size: 13pt; line-height: 1; letter-spacing: 1.4pt;">{{ mb_strtoupper($title) }}</div>
        <div class="a it c" style="left: {{ $cx - 62 }}pt; top: 290pt; width: 126pt; font-size: 12.8pt; line-height: 1.25;">{{ $text }}</div>
    @endforeach

    <div class="a" style="left: 52pt; top: 424pt; width: 853pt; height: 86pt; background: #2b231f; border: 0.8pt solid #6b5a4a; border-radius: 4pt;"></div>
    <div class="a oswb white" style="left: 63pt; top: 434pt; font-size: 24pt; line-height: 1.15; letter-spacing: 4pt;">TU RANCHO VALE MÁS<br>CON DATOS.</div>
    <div class="a it" style="left: 63pt; top: 494pt; font-size: 10.5pt; line-height: 1;">{{ $company }} · Inteligencia Aérea Cinegética</div>
    <table class="a" style="right: 64pt; top: 428pt;">
        @foreach ($contact as [$label, $value])
            <tr>
                <td class="oswb white r" style="height: 27pt; padding-right: 12pt; font-size: 13pt; line-height: 1; letter-spacing: 1.4pt;">{{ $label }}</td>
                <td class="white" style="font-size: 10pt; line-height: 1; letter-spacing: 0.5pt;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>
</div>
</body>
</html>
