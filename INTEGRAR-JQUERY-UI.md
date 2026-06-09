# Integración de jQuery + jQuery UI y layout `longProc`

Guía de la integración de **jQuery** y **jQuery UI** al build de **Vite** del proyecto, y del layout Blade **`longproc`** (con barra de progreso para "procesos largos") y su página demo.

---

## Tabla de contenidos

- [Instalación y compatibilidad de versiones](#instalación-y-compatibilidad-de-versiones)
- [Integración con Vite (wiring)](#integración-con-vite-wiring)
- [El gotcha del módulo diferido](#el-gotcha-del-módulo-diferido)
- [Layout `longproc` y la API `LongProc`](#layout-longproc-y-la-api-longproc)
- [Página demo](#página-demo)
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

## Notas y advertencias

- **`npm audit`** reporta 2 vulnerabilidades críticas que provienen de `jquery-ui-dist@1.13.3` (advisories de XSS conocidos de jQuery UI). No se corrió `npm audit fix` para no forzar cambios de versión que rompan los widgets. Evaluar subir a `jquery-ui-dist@^1.14` si se requiere.
- El JS de página **siempre** dentro de `DOMContentLoaded` (ver [el gotcha](#el-gotcha-del-módulo-diferido)).
- Las vistas deben cargar los assets con `@vite(['resources/css/app.css', 'resources/js/app.js'])` (el layout `longproc` ya lo hace).

---

_Documentación de la integración de jQuery UI — proyecto Lrvl_curso_reporte._
