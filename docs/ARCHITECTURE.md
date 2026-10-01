# Arquitectura · Espectro CRM

Documento de referencia técnica del CRM de Espectro Soluciones. Describe qué se construyó, por qué y dónde vive cada pieza.

## 1. Arquitectura seleccionada

| Capa | Tecnología | Motivo |
|------|------------|--------|
| Backend | Laravel 13 (PHP ≥ 8.3) | Estándar de facto en PHP, corre en cualquier hosting cPanel. |
| UI reactiva | Livewire 4 (componentes basados en clase) | Interactividad sin API REST ni SPA; todo se sirve desde PHP. |
| JS ligero | Alpine.js (incluido en Livewire) | Modales, multi-select, gráficas. |
| Estilos | Tailwind CSS 4 (compilado con Vite) | Diseño moderno y consistente; el CSS final es un archivo estático. |
| Gráficas | Chart.js (empaquetado en `public/build`) | Ligero y sin servidor. |
| Base de datos | MySQL 8 / MariaDB 10.4+ (SQLite para pruebas) | Lo que ofrece cPanel. |

**Producción sin Node.js.** Node/NPM solo se usa para compilar `public/build`. Los archivos compilados se versionan en el repositorio, de modo que el servidor solo necesita PHP + MySQL.

**Componentes Livewire basados en clase** (`app/Livewire/*` + `resources/views/livewire/*`). Livewire 4 propone archivos con emoji (`⚡nombre.blade.php`); se descartaron porque los nombres Unicode dan problemas con FTP y el administrador de archivos de algunos cPanel.

### Estructura de carpetas relevante

```
app/
  Enums/            Estados y catálogos fijos (etapas, estatus, roles…)
  Http/Controllers/ Login/logout (único controlador clásico)
  Http/Middleware/  EnsureUserIsActive
  Livewire/         Pantallas y formularios (uno por responsabilidad)
  Models/           Eloquent + scopes de consulta
  Observers/        Registro automático de historial
  Policies/         Autorización por entidad
  Services/         Reglas de negocio con varios pasos (cotizaciones, pagos, etapas, métricas, exportación)
  Support/          Helpers de formato (moneda, fechas, teléfono)
database/
  migrations/ seeders/ factories/
docs/               Arquitectura, métricas, instalación cPanel, respaldos
tests/Feature       Reglas críticas
```

## 2. Entidades

Las entidades principales usan **ULID** como llave primaria (`01J9Z3…`), por lo que las URLs son del tipo `/clientes/01j9z3k8…` y no exponen consecutivos. Los catálogos pequeños (especies, formas de pago, estados, configuración) usan enteros.

| Entidad | Tabla | Notas |
|---------|-------|-------|
| Usuario | `users` | `role` (administrador / usuario), `is_active`. |
| Cliente | `clients` | Nombre, teléfono normalizado, notas. Soft delete. |
| Rancho | `ranches` | Pertenece a un cliente. Municipio, estado (catálogo), liga de Maps, km redondo desde Monterrey, cerca, superficie, notas. Soft delete. |
| Oportunidad / Servicio | `opportunities` | **Registro central.** Pertenece a un rancho. Etapa, tipo de servicio, hectáreas cotizadas, fecha tentativa y fecha confirmada de censo, responsable, último contacto. Soft delete. |
| Especie | `species` + `opportunity_species` | Catálogo editable; relación muchos a muchos. |
| Cotización | `quotes` | Folio + versión (`COT-145`, `COT-145 V2`). Conserva IVA histórico y totales calculados. Soft delete. |
| Pago | `payments` | Pertenece a la oportunidad y se liga a la cotización aceptada vigente. Soft delete. |
| Forma de pago | `payment_methods` | Catálogo editable. |
| Tarea / seguimiento | `tasks` | Ligada opcionalmente a oportunidad, cliente y rancho. |
| Comentario | `comments` | Inmutable (no se edita ni se borra). |
| Historial | `activity_logs` | Línea de tiempo y auditoría. |
| Configuración | `settings` | Clave/valor (IVA, días de alerta). |
| Estado de México | `mexican_states` | Catálogo de las 32 entidades. |

## 3. Relaciones

```
Cliente 1 ── N Rancho 1 ── N Oportunidad 1 ── N Cotización 1 ── N Pago
                                   │
                                   ├── N Pago (todos los pagos del servicio)
                                   ├── N Tarea
                                   ├── N Comentario
                                   ├── N Historial
                                   └── N ── N Especie
```

* El cliente de una oportunidad **se deriva** del rancho (`opportunity → ranch → client`). No se duplica en la tabla.
* Las tareas guardan `client_id` y `ranch_id` además de `opportunity_id` porque pueden existir sin oportunidad (p. ej. "Llamar a Pepe"). Si se cambia el dueño de un rancho, las tareas se actualizan.
* El historial guarda `client_id`, `ranch_id` y `opportunity_id` del contexto para poder mostrar la línea de tiempo de cada nivel con una consulta indexada.

## 4. Reglas de negocio

1. Un cliente tiene N ranchos; un rancho pertenece a un cliente (FK obligatoria).
2. Un rancho tiene N oportunidades; cada oportunidad tiene su propio pipeline, cotizaciones, pagos y fecha de censo.
3. Una oportunidad tiene N cotizaciones. **Solo una puede estar `Aceptada`.** Se garantiza en dos niveles:
   * `QuoteService::accept()` marca como `Reemplazada` cualquier otra aceptada, dentro de una transacción.
   * Índice único `(opportunity_id, accepted_lock)` en BD: `accepted_lock = 1` solo cuando la cotización está aceptada (NULL en cualquier otro caso).
4. Cálculo de cotización (en `Quote::recalculate()`):
   * Subtotal = Servicio + Logística
   * Con IVA: IVA = Subtotal × % IVA ; Total = Subtotal + IVA
   * Sin IVA: IVA = 0 ; Total = Subtotal
   * `apply_vat` **no tiene valor por defecto implícito**: el formulario obliga a elegir “Con IVA” o “Sin IVA”.
   * El % de IVA se copia de Configuración al crear la cotización y se guarda en la propia cotización (histórico).
5. Versiones: “Nueva versión” crea `COT-145 V2` con los mismos datos; las versiones anteriores en Borrador/Enviada pasan a `Reemplazada`.
6. Las cotizaciones aceptadas no se editan; se crea una nueva versión.
7. Saldo = Total de la cotización aceptada − Σ pagos no eliminados de la oportunidad. Nunca se guarda: se calcula siempre.
8. Pagos: monto > 0, múltiples parciales. Si existe cotización aceptada, el pago se liga a ella.
9. **Censos programados no es una tabla.** Es la consulta `Opportunity::scheduled()` = etapa `Confirmado` **y** `census_date` no nula.
10. Registros financieros (cotizaciones y pagos) usan soft delete y dejan registro en el historial con monto y usuario. Solo administradores pueden eliminar.
11. Eliminaciones protegidas: no se puede eliminar un cliente con ranchos, un rancho con servicios, una oportunidad con pagos ni una cotización aceptada con pagos.
12. Avances automáticos de etapa (registrados en historial):
   * Al marcar una cotización como Enviada, si la oportunidad está en Prospecto → Cotización enviada.
   * Al aceptar una cotización, si la oportunidad está antes de Pendiente anticipo → Pendiente anticipo. Además, las hectáreas cotizadas de la oportunidad se igualan a las de la cotización aceptada.
13. Marcar como Perdido siempre pide confirmación (también desde el Kanban).
14. Último contacto: editable; se actualiza automáticamente al agregar un comentario o completar una tarea.
15. Próximo seguimiento: **derivado**, es la fecha más próxima de las tareas pendientes de la oportunidad.

Las fórmulas de métricas y KPIs están en [`METRICAS.md`](METRICAS.md).

## 5. Roles y permisos

`App\Enums\UserRole` define cada rol y su lista de permisos. Las policies preguntan `$user->hasPermission('...')`, nunca por el nombre del rol, así que agregar un rol nuevo es agregar un caso al enum.

| Permiso | Administrador | Usuario |
|---------|:-------------:|:-------:|
| `records.manage` (crear/editar clientes, ranchos, servicios, cotizaciones, pagos, tareas, comentarios) | ✔ | ✔ |
| `records.delete` (eliminar clientes, ranchos, servicios, cotizaciones, pagos) | ✔ | — |
| `users.manage` | ✔ | — |
| `settings.manage` (IVA, catálogos) | ✔ | — |

Usuarios desactivados no pueden iniciar sesión y se les cierra la sesión activa.

## 6. Historial de actividad

Los **observers** (`app/Observers`) registran automáticamente: creación/edición/eliminación de clientes, ranchos y oportunidades; cambio de etapa; cambio de fecha de censo; cotización creada/enviada/aceptada/rechazada/reemplazada/eliminada; pago registrado/eliminado; tarea creada/completada/cancelada; comentario agregado. Cada registro guarda fecha-hora, usuario, tipo de evento y descripción. Los comentarios no tienen edición ni borrado.

## 7. Navegación y pantallas

Menú lateral: **Dashboard · Pipeline · Clientes · Ranchos · Cotizaciones · Pagos · Censos programados · Seguimientos · Configuración**. Barra superior: buscador global + botón **Nuevo servicio**.

| Ruta | Pantalla (componente) |
|------|-----------------------|
| `/login` | Inicio de sesión (controlador) |
| `/` | `Dashboard` |
| `/pipeline` | `Pipeline\Board` (Kanban con drag & drop `wire:sort`) |
| `/clientes`, `/clientes/{client}` | `Clients\Index`, `Clients\Show` |
| `/ranchos`, `/ranchos/{ranch}` | `Ranches\Index`, `Ranches\Show` |
| `/servicios/nuevo` | `Opportunities\Create` (flujo rápido cliente → rancho → servicio) |
| `/servicios/{opportunity}` | `Opportunities\Show` (Resumen · Cotizaciones · Pagos · Seguimientos · Actividad) |
| `/cotizaciones` | `Quotes\Index` |
| `/pagos` | `Payments\Index` |
| `/censos` | `Census\Index` (Calendario mensual / Lista) |
| `/seguimientos` | `Tasks\Index` |
| `/configuracion/*` | `Settings\General`, `Settings\Users`, `Settings\SpeciesCatalog`, `Settings\PaymentMethods` |

Formularios en **modal**, montados una vez en el layout y abiertos por evento desde cualquier pantalla: `Forms\ClientForm`, `Forms\RanchForm`, `Forms\OpportunityForm`, `Forms\QuoteForm`, `Forms\PaymentForm`, `Forms\TaskForm`. Al guardar emiten `crm:refresh` y la pantalla visible se actualiza.

Breadcrumbs en fichas: Cliente → Rancho → Servicio.

## 8. Decisiones técnicas

* **Filtros persistentes** con `#[Session]` de Livewire: al salir y volver a una tabla se conservan búsqueda, filtros, orden y página.
* **Exportación CSV** con BOM UTF-8 (Excel lo abre con acentos correctos). Reutiliza la misma consulta filtrada que la tabla, por lo que respeta los filtros activos. Se evitó `maatwebsite/excel` para no depender de extensiones adicionales en el hosting.
* **Montos**: `decimal(12,2)`. Columna `currency` (`MXN`) en oportunidades, cotizaciones y pagos para habilitar USD en el futuro sin migrar datos.
* **Totales de cotización guardados**: una cotización es un documento histórico; se guardan subtotal, IVA y total calculados por el modelo (nunca capturados a mano). Todo lo demás (saldo, pagado, próximo seguimiento, métricas) se calcula.
* **Consultas sin N+1**: listas con `with()`, `withCount()` y subconsultas (`Opportunity::scopeWithFinancials`).
* **Fechas**: zona `America/Monterrey`; se muestran `DD/MM/YYYY` con el helper `fecha()`.
* **Moneda**: helper `money()` → `$75,000.00`.
* **Teléfonos**: se normalizan a 10 dígitos para México (o `+código…` si es internacional). WhatsApp usa `https://wa.me/52XXXXXXXXXX`.
* **Duplicados**: al capturar cliente se buscan coincidencias por teléfono y por similitud de nombre (sin acentos, `similar_text` ≥ 75 %); al capturar rancho, nombres parecidos del mismo cliente. Solo advierte, no bloquea.
* **Seguridad**: CSRF, escape de salida Blade, Form validation en español, `bcrypt`, `$fillable` explícito, Eloquent/Query Builder con parámetros, rate limit de 5 intentos por minuto en login, policies en cada acción Livewire.
* **Fuera de alcance v1**: WhatsApp/correo automáticos, Google Calendar, CFDI, documentos, API externas. Los puntos de extensión naturales son `app/Services` y los observers.
