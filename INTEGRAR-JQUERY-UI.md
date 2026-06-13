# Integración de jQuery + jQuery UI y layout `longProc`

Guía de la integración de **jQuery** y **jQuery UI** al build de **Vite** del proyecto, y del layout Blade **`longproc`** (con barra de progreso para "procesos largos") y su página demo.

---

## Tabla de contenidos

- [Instalación y compatibilidad de versiones](#instalación-y-compatibilidad-de-versiones)
- [Integración con Vite (wiring)](#integración-con-vite-wiring)
- [El gotcha del módulo diferido](#el-gotcha-del-módulo-diferido)
- [Layout `longproc` y la API `LongProc`](#layout-longproc-y-la-api-longproc)
- [Página demo](#página-demo)
- [longOps: diálogo de progreso (operaciones largas)](#longops-diálogo-de-progreso-operaciones-largas)
- [Notas y advertencias](#notas-y-advertencias)

---

## Instalación y compatibilidad de versiones

```bash
npm install jquery@^3.7.1 jquery-ui-dist
```

| Paquete | Versión | Por qué |
|---------|---------|---------|
| `jquery` | **3.7.1** | jQuery **4.0 NO es compatible** con jQuery UI 1.13: `jquery-ui-dist@1.13.3` declara `"jquery": ">=1.8.0 <4.0.0"`. jQuery 4 eliminó APIs que jQuery UI usa → rompe en runtime. |
| `jquery-ui-dist` | **1.13.3** | Es el paquete correcto para usar jQuery UI desde npm (trae los archivos compilados `jquery-ui.js` / `jquery-ui.css` + el theme). El paquete `jquery-ui` "pelado" no sirve para esto. |

> Si en el futuro se quiere jQuery 4, hay que subir a `jquery-ui-dist@^1.14` (que soporta jQuery más nuevo) y reprobar los widgets.

---

## Integración con Vite (wiring)

Instalar el paquete **no** lo carga: hay que importarlo en los entrypoints de Vite (`resources/js/app.js`, que ya incluye `resources/js/bootstrap.js`) y el CSS en `resources/css/app.css`.

**[resources/js/bootstrap.js](resources/js/bootstrap.js)** — jQuery global:

```js
import $ from 'jquery';
window.$ = window.jQuery = $;
```

**[resources/js/app.js](resources/js/app.js)** — jQuery UI **después** de bootstrap:

```js
import './bootstrap';
import 'jquery-ui-dist/jquery-ui.js';
```

**[resources/css/app.css](resources/css/app.css)** — el CSS/theme de jQuery UI:

```css
@import 'tailwindcss';
@import 'jquery-ui-dist/jquery-ui.css';
```

### Por qué ese orden

- jQuery UI espera el **global `jQuery`** al evaluarse. Por eso `bootstrap.js` deja `window.jQuery` **antes** de que `app.js` importe `jquery-ui`.
- Dentro de un mismo archivo, los `import` de ESM se **hoistean** y corren antes que el código normal. Si se pusiera `import 'jquery-ui'` en el mismo `bootstrap.js` después del `window.$ = …`, jQuery UI evaluaría **antes** de la asignación. Por eso jQuery UI se importa en `app.js` (módulo separado): cuando `app.js` evalúa `import 'jquery-ui'`, el módulo `bootstrap` ya terminó de ejecutarse y `window.jQuery` está definido.

Build:

```bash
npm run build      # producción
npm run dev        # desarrollo (o composer dev)
```

Tras compilar, `app.js` (~389 KB) incluye jQuery + jQuery UI y `app.css` (~80 KB) incluye el theme (los `ui-icons_*.png` se emiten como assets).

---

## El gotcha del módulo diferido

⚠️ **Importante.** `@vite([...])` inyecta `app.js` como `<script type="module">`, que es **diferido**: se ejecuta después de parsear el HTML, justo **antes** de `DOMContentLoaded`.

Por eso, un `<script>` inline en una vista que use jQuery **falla** si corre en tiempo de parseo:

```blade
{{-- ❌ MAL: este inline corre en el parseo, window.$ todavía no existe --}}
<script>
    $(function () { ... });   // ReferenceError: $ is not defined
</script>
```

La forma correcta es esperar a `DOMContentLoaded` (cuando el módulo de Vite ya dejó `window.jQuery`):

```blade
{{-- ✅ BIEN --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const $ = window.jQuery;
        // ... usar $ y los widgets de jQuery UI ...
    });
</script>
```

(Alternativa: escuchar el evento `longproc:ready` que dispara el layout — ver abajo.)

---

## Layout `longproc` y la API `LongProc`

**[resources/views/layouts/longproc.blade.php](resources/views/layouts/longproc.blade.php)** es un layout maestro pensado para procesos largos.

Secciones / stacks disponibles:

| Bloque | Uso |
|--------|-----|
| `@yield('title')` | `<title>` |
| `@yield('header')` | título del encabezado |
| `@yield('toolbar')` | botones a la derecha del encabezado |
| `@yield('content')` | contenido principal |
| `@stack('styles')` / `@stack('scripts')` | CSS / JS por página |

Trae una **barra de progreso de jQuery UI** (oculta por defecto) controlable con la API global `window.LongProc`:

| Método | Efecto |
|--------|--------|
| `LongProc.start(label?)` | muestra el panel y reinicia a 0% |
| `LongProc.set(valor, label?)` | actualiza % (0..100) y etiqueta |
| `LongProc.indeterminate(label?)` | modo indeterminado (sin %) |
| `LongProc.done(label?)` | marca 100% |
| `LongProc.fail(msg?)` | muestra un error |
| `LongProc.hide()` | oculta el panel |

La API se crea en `DOMContentLoaded` (cuando jQuery UI ya cargó) y al terminar dispara el evento `document` → `longproc:ready`.

### Cómo extenderlo

```blade
@extends('layouts.longproc')

@section('title', 'Mi proceso')
@section('header', 'Generación masiva')

@section('content')
    <button id="btn" class="px-4 py-2 bg-blue-600 text-white rounded">Generar</button>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const $ = window.jQuery;
        $('#btn').on('click', function () {
            LongProc.start('Procesando…');
            LongProc.set(50, 'Mitad…');
            LongProc.done('Listo');
        });
    });
</script>
@endpush
```

---

## Página demo

Genera el reporte `rpt_min02` para varios establecimientos mostrando la barra de progreso real.

| Pieza | Archivo |
|-------|---------|
| Vista | [resources/views/reportes/min02_demo.blade.php](resources/views/reportes/min02_demo.blade.php) |
| Controlador | [ReporteController::min02Demo()](app/Http/Controllers/ReporteController.php) |
| Ruta | `GET /reporte/min_02-demo` (nombre `reporte.min02.demo`) |

> El path es `min_02-demo` (no `min_02/...`) a propósito, para no colisionar con la ruta paramétrica `reporte/min_02/{estab?}/{anio?}`.

Qué hace: toma una lista de establecimientos (separados por coma) y un año; por cada uno hace `fetch` a `/reporte/min_02/{estab}/{anio}`, actualiza la barra (`LongProc.set/done/fail`) y lista cada PDF con su link (`ver PDF`) y tamaño, marcando errores en rojo.

Probar:

```bash
php artisan serve   # o composer dev
```

Abrir **http://localhost:8000/reporte/min_02-demo** → **Generar**.

---

## longOps: diálogo de progreso (operaciones largas)

Helper [resources/js/longops/longops.jQuery.js](resources/js/longops/longops.jQuery.js) (Selifonov, 2013): abre un **diálogo modal de jQuery UI con barra de progreso** que va consultando un backend por AJAX hasta terminar. A diferencia de `LongProc` (que maneja vos el % desde JS), acá el **backend** dicta el progreso.

### Carga

Se importa desde [app.js](resources/js/app.js) (`import './longops/longops.jQuery.js'`) y queda como `window.longOps`. Requiere jQuery + jQuery UI (ya en el bundle).

### Protocolo del backend (texto plano)

El backend responde un string `"<estado>|<porcentaje>|<comentario>"`:

| `longops_action` | Backend responde | Efecto |
|------------------|------------------|--------|
| `start` | `working|0|…` | inicializa (estado en sesión) |
| `resume` | `working|<pct>|…` | avanza; el cliente vuelve a llamar `resume` |
| (fin) | `finished|100|…` | termina → `onSuccess`, autocierra |
| `abort` (al cancelar) | `aborted|<pct>|…` | cancela → `onCancel`, autocierra |

El cliente hace polling: `start → resume → resume → … → finished` (o `aborted` si se aprieta Cancelar). El estado de avance se guarda en **sesión** entre llamadas.

### Demo

| Pieza | Archivo / ruta |
|-------|----------------|
| Vista | [resources/views/reportes/longops_demo.blade.php](resources/views/reportes/longops_demo.blade.php) |
| Backend | [ReporteController::longopsBackend()](app/Http/Controllers/ReporteController.php) → `GET /reporte/longops/backend` (`reporte.longops.backend`) |
| Página | [ReporteController::longopsDemo()](app/Http/Controllers/ReporteController.php) → `GET /reporte/longops-demo` (`reporte.longops.demo`) |

```js
longOps.start({ total: 10 }, {
    title: 'Procesando…',
    backend: '{{ route('reporte.longops.backend') }}',
    btnStop: 'Cancelar',
    autoClose: 2,                 // segundos para autocerrar al terminar/cancelar
    onSuccess: fn, onCancel: fn, onError: fn,
});
```

Backend (GET, en grupo `web` para tener sesión; sin CSRF por ser GET):

```php
$action = $request->input('longops_action', 'start');
if ($action === 'start') { /* session: step=0,total */ return "working|0|Iniciando…"; }
if ($action === 'abort') { return "aborted|{$pct}|Cancelada."; }
// resume: step++ ; return $step>=$total ? "finished|100|…" : "working|{$pct}|Paso {$step}…";
```

### Dos arreglos necesarios (jQuery moderno)

El helper es de 2013 (jQuery 1.4 / UI 1.8) y necesitó dos correcciones para funcionar hoy:

1. **`window.longOps`** en vez de `longOps =` — los módulos ESM son *strict mode* y no permiten asignar a una variable global no declarada.
2. **`<div id='progress_bar'></div>`** en vez de `<div id='progress_bar' />` — el parser HTML5 de jQuery 3.x **no cierra** los `<div/>` auto-cerrados, y el resto del contenido quedaba anidado dentro del progressbar, rompiendo la barra (no actualizaba).
3. **autoClose también en `aborted`** — el cierre automático solo aplicaba a `finished`; al cancelar el diálogo quedaba abierto sin forma de cerrarlo (botón Cancelar deshabilitado, "X" oculta, botón Cerrar comentado). Ahora autocierra también al cancelar.

Además se **rehabilitó el botón "Cerrar"** del diálogo (estaba comentado). Por `beforeclose()` solo cierra cuando `b_canClose` es true (al finalizar/cancelar), así que es seguro y resuelve el caso `autoClose: 0`.

Probar: **http://localhost:8000/reporte/longops-demo** → **Iniciar operación larga** (probá también **Cancelar**).

### Reporte por área (longOps + merge de PDFs)

Caso real: generar el reporte de **todos los establecimientos de un área** combinados en un PDF. Como un área puede tener cientos de establecimientos, se usa longOps para procesarla en tandas.

| Pieza | Detalle |
|-------|---------|
| Vista | [resources/views/reportes/longops_area_demo.blade.php](resources/views/reportes/longops_area_demo.blade.php) |
| Página | `GET /reporte/longops-area-demo` (`reporte.longops.areaDemo`) — desplegable de áreas + año + límite |
| Backend | [ReporteController::longopsBackendArea()](app/Http/Controllers/ReporteController.php) → `GET /reporte/longops/backendArea/{area?}/{anio?}` (`reporte.longops.backendArea`) |
| Descarga | `GET /reporte/longops/area-result/{job}` (`reporte.longops.areaResult`) |
| Modelo | [AreaPofP::getEstablecimientosArea($area, $anio)](app/Models/AreaPofP.php) — IDs de establecimientos del área (vía modalidad) con POF del año |

Flujo: `start` arma la lista de establecimientos del área/año (en sesión) → cada `resume` genera el `rpt_min02` de un lote de establecimientos (dentro de un presupuesto de tiempo, guardando los PDF en `storage/app/longops/<job>/`) → al terminar, **mergea todo con PDFMerger** (los `rpt_min02` son TCPDF clásico → compatibles) y el comentario `finished` trae el **link de descarga** del PDF del área.

La vista usa `autoClose: 0` (para no cerrar el diálogo y poder clickear el link) + botón **Cerrar**.

```js
longOps.start(
    { area: 'F', anio: 2020, limit: 5 },           // limit=0 → todos
    { backend: '{{ route('reporte.longops.backendArea') }}',
      btnStop: 'Cancelar', btnClose: 'Cerrar', autoClose: 0 }
);
```

#### Carpeta de la tanda: parámetro `$l_job`

`longopsBackendArea(Request $request, $area = null, $anio = null, $l_job = true)`. El **`job`** es el nombre **completo** de la carpeta de la tanda (`storage/app/longops/<job>/`) y es lo que comparten `start`, `resume` y la descarga:

| `$l_job` | Carpeta (`job`) | Uso |
|----------|-----------------|-----|
| `true` (default) | `<area>_<anio>_<hash>` (sufijo único) | corridas concurrentes/repetidas sin pisarse |
| `false` | `<area>_<anio>` (fija) | ruta de salida predecible/reusable; en `start` se limpian los PDF previos |

Se puede forzar por query para probar: `?l_job=0`. Ejemplos verificados:
`/reporte/longops/backendArea?area=F&anio=2020` → `job=F_2020_10128a05`; con `&l_job=0` → `job=F_2020`. Ambos terminan en un PDF de ~224 KB (5 establecimientos del área F).

> El **merge final** carga todos los PDFs de la tanda en memoria; para áreas muy grandes conviene usar el `limit` o subir memoria. Las carpetas `storage/app/longops/<job>/` quedan con el resultado (falta una limpieza por TTL).

#### Numeración continua y `rpt_min02` standalone

Para numerar las páginas en forma continua entre establecimientos, `rpt_min02` acumula un total de páginas (`generarImpresion(&$n_totPg)`) que lee/escribe en la sesión `longop_area`. Como esa sesión **solo existe en el flujo por área**, `rpt_min02` debe **tolerar su ausencia** al llamarse directo (`GET /reporte/min_02`): usa `$n_totPg = is_array($state) ? ($state['n_totPg'] ?? 0) : 0;` y solo persiste `if (is_array($state))`. (Si no, una llamada directa daba **500** por `$state` null.)

Probar: **http://localhost:8000/reporte/longops-area-demo** (área **F**, año 2020, límite 5 por defecto → rápido).

---

## Notas y advertencias

- **`npm audit`** reporta 2 vulnerabilidades críticas que provienen de `jquery-ui-dist@1.13.3` (advisories de XSS conocidos de jQuery UI). No se corrió `npm audit fix` para no forzar cambios de versión que rompan los widgets. Evaluar subir a `jquery-ui-dist@^1.14` si se requiere.
- El JS de página **siempre** dentro de `DOMContentLoaded` (ver [el gotcha](#el-gotcha-del-módulo-diferido)).
- Las vistas deben cargar los assets con `@vite(['resources/css/app.css', 'resources/js/app.js'])` (el layout `longproc` ya lo hace).

---

_Documentación de la integración de jQuery UI — proyecto Lrvl_curso_reporte._
