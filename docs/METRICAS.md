# Métricas y KPIs

Cómo se calcula cada cifra del Dashboard. Código: `app/Services/MetricsService.php` y `app/Services/AttentionService.php`.

**Reglas generales**

* Nunca cuentan registros eliminados (soft delete): oportunidades, cotizaciones ni pagos.
* "Ganadas" = oportunidades en etapa **Confirmado** o **Censo realizado**.
* "Abiertas" = **Prospecto, Cotización enviada, Seguimiento, Pendiente anticipo**.
* "Monto cotizado" de una oportunidad = total de su cotización **aceptada**; si no tiene, el total de su cotización más reciente en Borrador o Enviada. Las rechazadas y reemplazadas no cuentan.
* Montos en MXN, con IVA cuando la cotización lo incluye (el total real que paga el cliente).

## KPIs

| KPI | Fórmula |
|-----|---------|
| **Ventas confirmadas** | Σ `total` de cotizaciones **aceptadas** de oportunidades ganadas. |
| **Hectáreas confirmadas** | Σ `hectáreas cotizadas` de oportunidades ganadas. |
| **Censos programados** | Número de oportunidades en Confirmado con fecha de censo **≥ hoy**. |
| **Pipeline** | Σ monto cotizado de oportunidades abiertas. Las abiertas sin cotización suman $0. |
| **Saldo pendiente** | Σ por oportunidad ganada de `max(0, total aceptado − pagos)`. Un sobrepago en un servicio no compensa el saldo de otro. |
| **Seguimientos vencidos** | Tareas en estado Pendiente con fecha de vencimiento **anterior a hoy**. |

> **Diferencia con el documento original.** El requerimiento pedía hectáreas solo de oportunidades *Confirmadas*. Se incluyen también las de *Censo realizado* para que las hectáreas no "desaparezcan" del indicador al terminar el censo y sean consistentes con Ventas confirmadas. Si se prefiere la definición estricta, basta cambiar `Opportunity::won()` por `where('stage', 'confirmado')` en `confirmedHectares()`.

## Indicadores

| Indicador | Fórmula |
|-----------|---------|
| **Ticket promedio** | Ventas confirmadas ÷ número de oportunidades ganadas con cotización aceptada. |
| **Promedio de hectáreas por censo** | Hectáreas confirmadas ÷ número de oportunidades ganadas con hectáreas > 0. |
| **Tasa de cierre** | Ganadas ÷ (Ganadas + Perdidas). Solo considera oportunidades **cerradas**; las abiertas no la distorsionan porque aún no tienen resultado. |
| **Conversión global** | Ganadas ÷ todas las oportunidades (abiertas + cerradas). Mide qué porción del total de prospectos se ha convertido hasta hoy; baja cuando entran muchos prospectos nuevos. |
| **Censos realizados** | Oportunidades en etapa Censo realizado. |
| **Censos programados** | Igual que el KPI. |

La tasa de cierre es la métrica principal de efectividad comercial; la conversión global sirve para ver el volumen pendiente de trabajar.

## Gráficas

| Gráfica | Cálculo |
|---------|---------|
| Oportunidades por etapa | Conteo de todas las oportunidades por etapa actual. |
| Ventas por mes | Σ total aceptado de oportunidades ganadas, agrupado por **mes de aceptación** de la cotización, en el año elegido. |
| Hectáreas confirmadas por mes | Σ hectáreas cotizadas de oportunidades ganadas, agrupado por **mes de la fecha de censo** (cuándo se trabaja). |
| Oportunidades por estado / municipio | Conteo de **todas** las oportunidades (cualquier etapa) según la ubicación del rancho. Mide de dónde viene la demanda. |

## "Requieren atención"

| Alerta | Regla |
|--------|-------|
| Seguimientos vencidos | Tareas pendientes con fecha < hoy. |
| Cotizaciones enviadas sin seguimiento | Cotización en **Enviada**, enviada hace ≥ N días (Configuración, 3 por defecto), oportunidad abierta y **sin actividad posterior al envío**: sin comentarios, sin tareas creadas o completadas, sin tareas pendientes, sin pagos y sin último contacto posterior. |
| Censos próximos con saldo | Censos programados dentro de los próximos N días (Configuración, 7 por defecto) sin cotización aceptada o con saldo > 0. |
| En Seguimiento sin próxima tarea | Etapa Seguimiento sin ninguna tarea pendiente. |
| Pendiente anticipo sin pago | Etapa Pendiente anticipo sin pagos registrados. |
| Censos con fecha pasada | Etapa Confirmado con fecha de censo < hoy (falta marcarlos como Censo realizado). |

## Cliente

* **Total vendido**: Σ total aceptado de sus oportunidades ganadas.
* **Saldo pendiente**: total vendido − pagos de esas oportunidades.
* **Último contacto**: el más reciente de sus oportunidades. Se actualiza solo al agregar un comentario o completar una tarea, y también puede editarse en el servicio.
* **Próximo seguimiento**: la tarea pendiente más próxima del cliente.
