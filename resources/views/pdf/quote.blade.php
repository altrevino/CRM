{{--
    PDF de cotización con el diseño de la propuesta COT-159 (Canva): láminas 16:9,
    Oswald + Source Serif, paleta café/crema/vino. Textos fijos tomados de COT-159.
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
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Serif'; font-size: 11pt; line-height: 1.35; }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: top; padding: 0; }

    .slide { position: relative; width: 960pt; height: 538pt; overflow: hidden; page-break-after: always; padding: 0; }
    .inner { padding: 30pt 40pt 0 40pt; }
    .slide.last { page-break-after: auto; }
    .dark { background: #1a1410; color: #d8c4a0; }
    .light { background: #fbf8f4; color: #1a1410; }
    .cream { background: #f1ece6; color: #1a1410; }

    .kicker { font-family: 'Oswald'; font-size: 10.5pt; letter-spacing: 1.6pt; text-transform: uppercase; color: #9a8b7b; }
    .h1 { font-family: 'Oswald'; font-weight: bold; font-size: 38pt; line-height: 0.95; text-transform: uppercase; margin: 14pt 0 0; letter-spacing: -0.5pt; }
    .red { color: #8b2e2e; }
    .tan { color: #d6c2a1; }
    .white { color: #ffffff; }
    .copper { color: #8a5a3a; }
    .it { font-style: italic; }
    .osw { font-family: 'Oswald'; }
    .oswl { font-family: 'OswaldLight'; }
    .up { text-transform: uppercase; }
    .card { background: #2b2320; color: #f1ece6; border-radius: 4pt; }
    .num { font-family: 'Oswald'; font-weight: bold; color: #8b2e2e; font-size: 26pt; line-height: 1; }
    .item-title { font-family: 'Oswald'; font-weight: bold; font-size: 13pt; text-transform: uppercase; color: #1a1410; }
    .item-text { font-family: 'OswaldLight'; font-size: 11.5pt; line-height: 1.3; color: #1a1410; }
    .vline { border-left: 1pt solid #1a1410; }
</style>
</head>
<body>
@php
    $complete = $quote->opportunity->service_type === \App\Enums\ServiceType::Complete;
    $modality = $complete ? 'Levantamiento integral' : 'Censo '.mb_strtolower($quote->opportunity->service_type->label());
    $ha = (float) $quote->hectares;
    $haText = number_format($ha, fmod($ha, 1.0) == 0.0 ? 0 : 2);
    $location = $ranch->location();
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
    $whatsapp = $business['whatsapp'] ?? '';
@endphp

{{-- 1. Portada --}}
<div class="slide dark">
<div class="inner">
    <table>
        <tr>
            <td><img src="{{ $logo }}" style="width: 96pt;" alt=""></td>
            <td style="text-align: right;">
                <div class="osw" style="font-size: 14pt; color: #d6c2a1;">PROPUESTA DE SERVICIOS</div>
                <div class="osw" style="font-size: 10.5pt; color: #9a8b7b; margin-top: 4pt; letter-spacing: 1pt;">COTIZACIÓN {{ $quote->number }} · {{ fecha($quote->issued_at) }}</div>
            </td>
        </tr>
    </table>
    <div style="margin-top: 14pt; margin-left: 41pt;">
        <div class="it" style="font-size: 11pt; color: #d6c2a1;">Decisiones basadas en datos reales</div>
        <div class="osw white" style="font-weight: bold; font-size: 44pt; line-height: 0.95; margin-top: 2pt;">LEVANTAMIENTO Y ANÁLISIS<br>DE FAUNA MEDIANTE DRON<br><span class="red">TÉRMICO</span></div>
        <div style="width: 68pt; height: 3pt; background: #d6c2a1; margin-top: 4pt;"></div>
        <div class="osw" style="font-size: 14pt; letter-spacing: 5pt; margin-top: 8pt; color: #d6c2a1;">INTELIGENCIA AÉREA PARA MANEJO CINEGÉTICO</div>
    </div>
    <div style="position: absolute; left: 19pt; right: 25pt; top: 428pt; border-top: 0.8pt solid #8a7a62;"></div>
    <table style="position: absolute; left: 26pt; right: 25pt; top: 434pt; width: 909pt;">
        <tr>
            <td style="width: 60%; padding-top: 14pt;">
                <div class="osw" style="font-weight: bold; font-size: 14pt; letter-spacing: 1.5pt; color: #d6c2a1;">{{ $ranch->name }}</div>
                <div class="osw" style="font-size: 12pt; color: #d6c2a1; margin-top: 2pt;">{{ $location ?: 'Ubicación por confirmar' }}</div>
                <div class="it" style="font-size: 10pt; color: #9a8b7b; margin-top: 4pt;">Preparada para {{ $client->name }}</div>
            </td>
            <td style="text-align: right;">
                <div class="osw" style="font-weight: bold; font-size: 30pt; line-height: 1; color: #d6c2a1;">{{ $haText }}</div>
                <div class="osw" style="font-weight: bold; font-size: 13pt; letter-spacing: 2pt; color: #d6c2a1;">HECTÁREAS</div>
                <div class="osw" style="font-size: 11pt; letter-spacing: 1.5pt; color: #d6c2a1; margin-top: 4pt;">SUPERFICIE DEL PREDIO</div>
            </td>
        </tr>
    </table>
</div>
</div>

{{-- 2. Contexto del predio --}}
<div class="slide light">
<div class="inner">
    <img src="{{ $images['venado'] }}" style="position: absolute; right: 0; top: 0; height: 539pt;" alt="">
    <div style="position: relative;">
        <div class="kicker">{{ $business['name'] ?? 'Espectro Soluciones' }}</div>
        <div class="h1" style="font-size: 44pt;"><span class="red">Contexto</span> del predio</div>
        <table style="width: 560pt; margin-top: 28pt;">
            <tr>
                <td style="width: 150pt; padding-right: 14pt;">
                    <div class="osw" style="font-size: 13pt;">SUPERFICIE</div>
                    <div class="osw" style="font-weight: bold; font-size: 34pt; line-height: 1.05;">{{ $haText }}</div>
                    <div class="osw" style="font-size: 10pt; letter-spacing: 2.5pt;">HECTÁREAS</div>
                </td>
                <td style="border-left: 1.6pt solid #1a1410; padding: 4pt 14pt; width: {{ $fence ? '200pt' : '410pt' }};">
                    <div class="osw" style="font-size: 13pt;">UBICACIÓN</div>
                    <div class="osw" style="font-weight: bold; font-size: 17pt; margin-top: 8pt;">{{ $location ?: 'Por confirmar' }}</div>
                </td>
                @if ($fence)
                    <td style="border-left: 1.6pt solid #1a1410; padding: 4pt 14pt;">
                        <div class="osw" style="font-size: 13pt;">TIPO DE CERCA</div>
                        <div class="osw up" style="font-weight: bold; font-size: 17pt; margin-top: 8pt;">{{ $fence }}</div>
                    </td>
                @endif
            </tr>
        </table>
        <div class="card" style="width: 560pt; margin-top: 30pt; padding: 14pt 20pt;">
            <div class="osw" style="font-weight: bold; font-size: 14pt;">OBJETIVO DEL LEVANTAMIENTO</div>
            <div class="oswl" style="font-size: 13pt; margin-top: 4pt;">Estimar la población, estructura y distribución de fauna dentro del predio.</div>
        </div>
        @if ($complete)
            <div class="card" style="width: 560pt; margin-top: 14pt; padding: 14pt 20pt;">
                <div class="osw" style="font-weight: bold; font-size: 14pt;">RECOMENDACIÓN DE ESPECTRO</div>
                <div class="oswl" style="font-size: 13pt; margin-top: 4pt;">Realizar un levantamiento integral sobre las {{ $haText }} ha, aprovechando una sola ventana térmica y evitando estimaciones por muestreo.</div>
            </div>
        @endif
    </div>
</div>
</div>

{{-- 3. Modalidad (texto de COT-159, solo levantamiento integral) --}}
@if ($complete)
<div class="slide dark">
<div class="inner">
    <div class="kicker">Levantamiento y análisis de fauna mediante dron térmico</div>
    <table style="margin-top: 14pt;">
        <tr>
            <td style="width: 450pt; padding-right: 30pt;">
                <div class="osw" style="font-size: 11pt; letter-spacing: 1pt; color: #9a8b7b;">MODALIDAD</div>
                <div class="osw" style="font-weight: bold; font-size: 34pt; line-height: 0.95;"><span class="tan">LEVANTAMIENTO</span> <span class="red">INTEGRAL</span></div>
                <div style="font-size: 10.5pt; color: #fff; letter-spacing: .5pt;">COBERTURA DEFINIDA POR LAS CONDICIONES DEL LEVANTAMIENTO</div>
                <div style="font-size: 10.5pt; margin-top: 10pt;">El levantamiento se realiza mediante transectos planificados para recorrer sistemáticamente la superficie seleccionada, aprovechando las condiciones de mayor contraste térmico.</div>
                <div style="font-size: 10.5pt; margin-top: 7pt;">Las rutas de vuelo permiten documentar la cobertura efectiva del predio y respaldar el análisis de las detecciones registradas.</div>
                <div style="font-size: 10.5pt; margin-top: 7pt;">La superficie alcanzable depende de factores como:</div>
                <div style="font-size: 10.5pt; font-weight: bold; margin: 2pt 0 0 12pt; line-height: 1.3;">• Temperatura ambiente<br>• Calor acumulado en el terreno<br>• Época del año<br>• Vegetación y relieve</div>
                <div style="border: 0.8pt solid #6b5a4a; background: #2b2320; border-radius: 4pt; padding: 8pt 14pt; margin-top: 10pt;">
                    <div class="osw" style="font-size: 12pt; letter-spacing: 1pt; color: #fff;">CONSIDERACIÓN IMPORTANTE</div>
                    <div class="it" style="font-size: 10.5pt; margin-top: 3pt;">La prioridad es mantener condiciones adecuadas de detección y una cobertura sistemática, sin comprometer la calidad del levantamiento por alcanzar una superficie determinada.</div>
                </div>
            </td>
            <td>
                <table>
                    <tr>
                        <td style="width: 50%; text-align: center; padding-bottom: 12pt;">
                            <div class="osw" style="font-weight: bold; font-size: 28pt; line-height: 1; color: #d6c2a1;">95 - 98%</div>
                            <div class="it" style="font-size: 9.5pt;">Cobertura objetivo, sujeta<br>a condiciones operativas.</div>
                        </td>
                        <td style="text-align: center; padding-bottom: 12pt;">
                            <div class="osw" style="font-weight: bold; font-size: 22pt; line-height: 1; color: #d6c2a1;">COBERTURA<br>VERIFICABLE</div>
                            <div class="it" style="font-size: 9.5pt; margin-top: 4pt;">Rutas georreferenciadas para verificar la cobertura efectiva del predio.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding-bottom: 14pt;">
                            <div class="osw" style="font-weight: bold; font-size: 28pt; line-height: 1; color: #d6c2a1;">1 NOCHE</div>
                            <div class="it" style="font-size: 9.5pt;">Levantamiento diseñado para aprovechar la ventana térmica disponible.</div>
                        </td>
                        <td style="text-align: center; padding-bottom: 14pt;">
                            <div class="osw" style="font-weight: bold; font-size: 28pt; line-height: 1; color: #d6c2a1;">GPS</div>
                            <div class="it" style="font-size: 9.5pt;">Cada detección georreferenciada<br>dentro del predio</div>
                        </td>
                    </tr>
                </table>
                <table style="border: 0.8pt solid #5a3b22; font-size: 10.5pt;">
                    <tr style="background: #5a3b22; color: #fff;">
                        <td class="osw" style="padding: 7pt; font-weight: bold; text-align: center;">CONDICIÓN</td>
                        <td class="osw" style="padding: 7pt; font-weight: bold; text-align: center;">DISEÑO</td>
                        <td class="osw" style="padding: 7pt; font-weight: bold; text-align: center;">RESULTADO</td>
                    </tr>
                    <tr>
                        <td class="osw" style="padding: 7pt; text-align: center; border: 0.8pt solid #5a3b22;">CONDICIONES<br>FAVORABLES</td>
                        <td style="padding: 7pt; text-align: center; border: 0.8pt solid #5a3b22;">Mayor ventana operativa</td>
                        <td style="padding: 7pt; text-align: center; border: 0.8pt solid #5a3b22;">Mayor cobertura</td>
                    </tr>
                    <tr>
                        <td class="osw" style="padding: 7pt; text-align: center; border: 0.8pt solid #5a3b22;">CONDICIONES<br>LIMITANTES</td>
                        <td style="padding: 7pt; text-align: center; border: 0.8pt solid #5a3b22;">Ajuste de horario y alcance</td>
                        <td style="padding: 7pt; text-align: center; border: 0.8pt solid #5a3b22;">Se prioriza la calidad de detección.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="it" style="position: absolute; left: 40pt; right: 40pt; top: 496pt; font-size: 8pt; line-height: 1.2;">*La cobertura indicada es un objetivo operativo, no una garantía. La superficie efectivamente levantada y la capacidad de detección dependen de las condiciones ambientales, vegetación, relieve y comportamiento de la fauna. Los resultados corresponden a los ejemplares detectados y validados durante el censo.</div>
</div>
</div>
@endif

{{-- 4. Metodología y protocolo --}}
<div class="slide light">
<div class="inner">
    <img src="{{ $images['rutas'] }}" style="position: absolute; right: 0; top: 14pt; width: 180pt;" alt="">
    <img src="{{ $images['equipo'] }}" style="position: absolute; right: 0; top: 250pt; width: 200pt;" alt="">
    <div class="kicker">{{ $business['name'] ?? 'Espectro Soluciones' }}</div>
    <div class="h1"><span class="red">Metodología</span> y protocolo</div>
    @php
        $steps = [
            ['01', 'Planeación', 'Diseño de rutas, altura y separación de transectos según vegetación, relieve y características del predio.'],
            ['02', 'Levantamiento', 'Barrido térmico sistemático mediante rutas planificadas, aprovechando las condiciones de mayor contraste térmico.'],
            ['03', 'Detección y validación', 'Identificación de fauna mediante imagen térmica y confirmación visual cuando las condiciones lo permiten.'],
            ['04', 'Bitácora digital', 'Registro georreferenciado de detecciones, incluyendo especie, sexo y clasificación disponible.'],
            ['05', 'Auditoría de detecciones', 'Revisión digital de registros para identificar posibles duplicidades, verificar clasificaciones y consolidar el conteo.'],
            ['06', 'Análisis y reporte', 'Procesamiento de datos para generar densidades, estructura poblacional, mapas y recomendaciones de manejo.'],
        ];
    @endphp
    <table style="width: 680pt; margin-top: 14pt;">
        @foreach (array_chunk($steps, 3) as $r => $row)
            <tr>
                @foreach ($row as $c => [$n, $t, $d])
                    <td style="width: 226pt; padding: {{ $r ? '10pt' : '0' }} 12pt {{ $r ? '0' : '10pt' }} {{ $c ? '12pt' : '0' }}; {{ $c ? 'border-left: 1pt solid #1a1410;' : '' }} {{ $r ? '' : 'border-bottom: 1pt solid #1a1410;' }}">
                        <table><tr>
                            <td style="width: 40pt;"><div class="num">{{ $n }}</div></td>
                            <td><div class="item-title">{{ $t }}</div><div class="item-text">{{ $d }}</div></td>
                        </tr></table>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
    <div class="card" style="position: absolute; left: 20pt; top: 380pt; width: 680pt; padding: 14pt 26pt 16pt;">
        <div class="osw" style="font-size: 15pt;">CADA DETECCIÓN SE REGISTRA, REVISA Y ANALIZA ANTES DE INTEGRARSE AL REPORTE.</div>
        <table style="margin-top: 10pt;">
            <tr>
                <td style="width: 33%; padding-right: 14pt;"><div class="osw" style="font-size: 12.5pt;">REGISTRO ESTRUCTURADO</div><div class="oswl" style="font-size: 11pt; margin-top: 3pt;">Bitácora digital con información georreferenciada de las detecciones.</div></td>
                <td style="width: 33%; padding-right: 14pt;"><div class="osw" style="font-size: 12.5pt;">CONTROL DE DUPLICIDADES</div><div class="oswl" style="font-size: 11pt; margin-top: 3pt;">Herramientas de auditoría para identificar y revisar posibles registros repetidos.</div></td>
                <td><div class="osw" style="font-size: 12.5pt;">TRAZABILIDAD</div><div class="oswl" style="font-size: 11pt; margin-top: 3pt;">Relación entre detecciones, ubicaciones y evidencia disponible para su revisión.</div></td>
            </tr>
        </table>
    </div>
</div>
</div>

{{-- 5. Tecnología propia --}}
<div class="slide light">
<div class="inner">
    <div class="kicker">{{ $business['name'] ?? 'Espectro Soluciones' }}</div>
    <div class="h1"><span class="red">Tecnología</span> propia para el <span class="red">registro y auditoría</span> de detecciones</div>
    <table style="position: absolute; left: 40pt; top: 178pt; width: 880pt;">
        <tr>
            <td style="width: 50%; padding: 0 30pt 0 45pt;">
                <img src="{{ $images['bitacora'] }}" style="height: 185pt;" alt="">
                <div class="osw copper" style="font-weight: bold; font-size: 17pt; text-align: center; margin-top: 6pt;">BITÁCORA DIGITAL</div>
                <div class="oswl" style="font-size: 12pt; margin-top: 2pt;">Registro digital de cada detección, integrando especie, sexo, ubicación y trazabilidad durante el levantamiento.</div>
            </td>
            <td style="padding: 0 45pt 0 30pt;">
                <img src="{{ $images['auditoria'] }}" style="height: 185pt;" alt="">
                <div class="osw copper" style="font-weight: bold; font-size: 17pt; text-align: center; margin-top: 6pt;">AUDITORÍA DIGITAL</div>
                <div class="oswl" style="font-size: 12pt; margin-top: 2pt;">Análisis espacial y temporal de las detecciones para identificar posibles duplicidades, revisar registros y reducir errores de conteo.</div>
            </td>
        </tr>
    </table>
</div>
</div>

{{-- 6. Entregables --}}
<div class="slide light">
<div class="inner">
    <div class="kicker">{{ $business['name'] ?? 'Espectro Soluciones' }}</div>
    <div class="h1">Lo que recibes al final del <span class="red">levantamiento</span></div>
    @php
        $deliverables = [
            ['01', 'Conteo por especie', 'Inventario de ejemplares detectados y validados, clasificados por especie, sexo y edad cuando las condiciones lo permiten.'],
            ['02', 'Mapa georreferenciado', 'Distribución espacial de las detecciones para identificar zonas de concentración y presencia de fauna.'],
            ['03', 'Análisis de densidades', 'Cálculo de densidades por especie y superficie censada, con indicadores de abundancia poblacional.'],
            ['04', 'Estructura poblacional', 'Análisis de la relación macho-hembra, presencia de crías y composición de las poblaciones registradas.'],
            ['05', 'Dashboard de resultados', 'Visualización de densidades, estructura poblacional y distribución de fauna mediante gráficas e indicadores.'],
            ['06', 'Reporte técnico', 'Resultados consolidados, metodología, mapas, evidencia visual, limitaciones y recomendaciones de manejo.'],
        ];
    @endphp
    <table style="margin-top: 22pt;">
        @foreach (array_chunk($deliverables, 3) as $r => $row)
            <tr>
                @foreach ($row as $c => [$n, $t, $d])
                    <td style="width: 33.3%; padding: {{ $r ? '14pt' : '0' }} 14pt {{ $r ? '0' : '14pt' }} {{ $c ? '16pt' : '6pt' }}; {{ $c ? 'border-left: 1pt solid #1a1410;' : '' }} {{ $r ? '' : 'border-bottom: 1pt solid #1a1410;' }}">
                        <table><tr>
                            <td style="width: 46pt;"><div class="num" style="font-size: 30pt;">{{ $n }}</div></td>
                            <td><div class="item-title" style="font-size: 14pt;">{{ $t }}</div><div class="item-text" style="font-size: 12.5pt; margin-top: 6pt;">{{ $d }}</div></td>
                        </tr></table>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
    <div class="card" style="position: absolute; left: 81pt; right: 57pt; top: 440pt; padding: 14pt 24pt;">
        <table><tr>
            <td class="osw" style="width: 130pt; font-weight: bold; font-size: 15pt; padding-top: 2pt;">ENTREGABLE FINAL:</td>
            <td class="oswl" style="font-size: 13pt;">Reporte técnico + dashboard de resultados + mapa interactivo + tabla de detecciones + evidencia térmica y RGB.</td>
        </tr></table>
    </div>
</div>
</div>

{{-- 7. Inversión --}}
<div class="slide cream">
<div class="inner" style="padding-top: 22pt;">
    <div class="kicker">Inversión · {{ $modality }} · {{ $quote->number }}</div>
    <div class="h1" style="font-size: 38pt; margin-top: 4pt;"><span class="red">{{ \Illuminate\Support\Str::before(mb_strtoupper($modality), ' ') }}</span> {{ \Illuminate\Support\Str::after(mb_strtoupper($modality), ' ') }}.</div>
    <div class="it" style="font-size: 11pt;">Propuesta vigente hasta el {{ fecha($quote->valid_until) }} · Emitida el {{ fecha($quote->issued_at) }}{{ $quote->tentative_service_date ? ' · Fecha tentativa del censo: '.fecha($quote->tentative_service_date) : '' }}</div>

    <div style="background: #1a1410; border-radius: 4pt; margin-top: 10pt; padding: 12pt 14pt 12pt;">
        <div class="osw" style="font-weight: bold; font-size: 18pt; color: #d6c2a1; padding: 0 0 0 0;">CENSO TÉRMICO {{ $complete ? 'INTEGRAL ' : '' }}CON AUDITORÍA DIGITAL</div>
        @if ($scope)
            <div class="it" style="font-size: 11.5pt; color: #d6c2a1;">{{ implode(' · ', $scope) }}</div>
        @endif
        <div style="border-top: 0.8pt solid #8a7a62; margin: 6pt 0 4pt;"></div>
        <table>
            <tr>
                @foreach ($columns as [$label, $value, $unit])
                    <td style="text-align: center; width: {{ round(100 / count($columns), 2) }}%; padding: 2pt 4pt 6pt;">
                        <div class="osw up" style="font-size: 13pt; color: #d6c2a1;">{{ $label }}</div>
                        <div class="osw" style="font-weight: bold; font-size: {{ count($columns) > 3 ? '24pt' : '28pt' }}; color: #fff; margin-top: 4pt;">{{ $value }}</div>
                        @if ($unit)<div class="osw up" style="font-size: 10.5pt; color: #d6c2a1;">{{ $unit }}</div>@endif
                    </td>
                @endforeach
            </tr>
        </table>
        <table style="background: #b98f4b; margin-top: 4pt;">
            <tr>
                <td class="osw" style="width: 30%; padding: 8pt 0 6pt 40pt; font-weight: bold; font-size: 28pt; color: #1a1410;">TOTAL:</td>
                <td style="padding: 6pt 30pt 6pt 0; color: #1a1410;">
                    <span class="osw" style="font-weight: bold; font-size: 28pt;">{{ money($quote->total) }}</span>
                    <span class="osw" style="font-size: 13pt;"> MXN {{ $quote->apply_vat ? 'IVA incluido' : '· No incluye IVA' }}</span>
                    @if ($quote->apply_vat)
                        <div class="osw" style="font-size: 10.5pt; margin-top: 2pt;">Subtotal {{ money($quote->subtotal) }} + IVA {{ $vatPct }}% {{ money($quote->vat_amount) }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    @if ($terms->isNotEmpty())
        <table style="margin-top: 8pt; background: #fff; border: 0.8pt solid #d6c2a1;">
            <tr>
                @foreach ($terms as $term)
                    <td style="padding: 6pt 12pt; font-size: 11pt; color: #6b3a1e; text-align: center;">• {{ $term }}</td>
                @endforeach
            </tr>
        </table>
    @endif
    @if ($separateCharges || $quote->observations)
        <div style="position: absolute; left: 40pt; right: 40pt; top: 500pt; font-size: 8.5pt; line-height: 1.15; color: #4a3b2f;">
            @if ($quote->observations)<strong>Observaciones:</strong> {{ $quote->observations }}@if ($separateCharges) · @endif @endif{{ $separateCharges }}
        </div>
    @endif
</div>
</div>

{{-- 8. Siguientes pasos --}}
<div class="slide dark last">
<div class="inner">
    <div class="kicker">Siguientes pasos</div>
    <div class="h1" style="color: #d6c2a1;">Del acuerdo al reporte en <span class="white">6 pasos</span></div>
    <div class="it" style="font-size: 11pt;">Del acuerdo inicial a la entrega del reporte técnico.</div>
    @php
        $next = [
            ['Confirmación', 'Definimos el alcance, la superficie y las condiciones del servicio.'],
            ['Anticipo y agenda', 'Reservamos la fecha del levantamiento mediante un anticipo del 25%.'],
            ['Planeación', 'Validamos el perímetro y diseñamos las rutas de vuelo considerando vegetación, relieve y características del predio.'],
            ['Levantamiento', 'Realizamos el barrido térmico sistemático y registramos las detecciones en la bitácora digital.'],
            ['Auditoría y análisis', 'Revisamos posibles duplicidades, validamos los registros y consolidamos los indicadores poblacionales.'],
            ['Resultados', 'Entregamos el reporte técnico, mapas, dashboard de resultados y evidencia visual.'],
        ];
    @endphp
    <div style="position: absolute; left: 90pt; right: 90pt; top: 196pt; border-top: 0.8pt solid #8a7a62;"></div>
    <table style="margin-top: 22pt;">
        <tr>
            @foreach ($next as $i => [$t, $d])
                <td style="width: 16.6%; text-align: center; padding: 0 6pt;">
                    <div style="width: 58pt; height: 58pt; border-radius: 29pt; background: #8a5a3a; margin: 0 auto; text-align: center;">
                        <div class="osw" style="font-weight: bold; font-size: 26pt; color: #fff; padding-top: 6pt;">{{ $i + 1 }}</div>
                    </div>
                    <div class="osw up" style="font-weight: bold; font-size: 11pt; letter-spacing: 1pt; color: #fff; margin-top: 12pt;">{{ $t }}</div>
                    <div class="it" style="font-size: 10pt; margin-top: 8pt;">{{ $d }}</div>
                </td>
            @endforeach
        </tr>
    </table>
    <div style="position: absolute; left: 40pt; right: 40pt; top: 410pt; border: 0.8pt solid #6b5a4a; background: #2b2320; border-radius: 4pt; padding: 12pt 18pt;">
        <table><tr>
            <td style="width: 55%;">
                <div class="osw" style="font-weight: bold; font-size: 21pt; letter-spacing: 2.5pt; color: #fff; line-height: 1.25;">TU RANCHO VALE MÁS<br>CON DATOS.</div>
                <div class="it" style="font-size: 9.5pt;">{{ $business['name'] ?? 'Espectro Soluciones' }} · Inteligencia Aérea Cinegética</div>
            </td>
            <td style="text-align: right; font-size: 10pt; color: #fff; padding-top: 2pt;">
                @if (! empty($business['website']))<div><span class="osw" style="font-weight: bold; letter-spacing: 1pt;">WEB</span>&nbsp;&nbsp; {{ $business['website'] }}</div>@endif
                @if (! empty($business['email']))<div style="margin-top: 6pt;"><span class="osw" style="font-weight: bold; letter-spacing: 1pt;">EMAIL</span>&nbsp;&nbsp; {{ $business['email'] }}</div>@endif
                @if ($whatsapp)<div style="margin-top: 6pt;"><span class="osw" style="font-weight: bold; letter-spacing: 1pt;">WHATSAPP</span>&nbsp;&nbsp; {{ $whatsapp }}</div>@endif
                @if (! empty($business['address']))<div style="margin-top: 6pt;">{{ $business['address'] }}</div>@endif
            </td>
        </tr></table>
    </div>
</div>
</div>
</body>
</html>
