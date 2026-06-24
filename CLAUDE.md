# CLAUDE.md

Contexto del proyecto para Claude Code. Es un destilado de alta señal; el detalle
está en `README.md`, `RESUMEN-APP.md` (bitácora por commits), `CHAT.md` (detalle extenso)
y las guías `INTEGRAR-*.md`. **No dupliques esos docs aquí**: solo lo que conviene tener
siempre a mano.

## Qué es

App **Laravel 12 / PHP 8.2** que genera **reportes PDF** de planta de cargos por
establecimiento/área. Migra un reporteador legacy (Symfony 1 + Doctrine 1 + TCPDF) a
Laravel y lee datos reales de una base **MySQL legacy** (`sdo_db`). Reporte principal:
**"Planta Completa Valorizada"** (`rpt_min02`), con versión **por área** (multi-establecimiento,
combinada en un PDF con barra de progreso).

## Entorno (⚠️ crítico)

- El `php` del PATH es **5.6.35** y el proyecto requiere **PHP >= 8.2**. Usar siempre el de XAMPP:
  `c:/eid/Xampp_82/php/php.exe` (PHP 8.2.12). Ej: `c:/eid/Xampp_82/php/php.exe artisan migrate`.
- Shell del sistema: PowerShell (también hay bash disponible).

## Comandos

```bash
c:/eid/Xampp_82/php/php.exe artisan serve          # servidor en http://localhost:8000
composer dev                                        # server + queue + logs + vite en paralelo
npm run dev | npm run build                         # assets (build necesario tras tocar JS/CSS)
composer setup                                      # instala todo; arma la DB importando el dump (NO migra)
c:/eid/Xampp_82/php/php.exe database/setup_sqlite.php       # (re)arma database.sqlite desde database.sqlite.sql
c:/eid/Xampp_82/php/php.exe artisan migrate:fresh --force   # solo para REGENERAR la DB por migraciones (fill_pof_p ~4 min)
composer test                                       # phpunit
./vendor/bin/pint                                   # linter/formato
```

> **`composer setup` NO corre migraciones**: arma `database/database.sqlite` importando el dump
> `database/database.sqlite.sql` (vía `database/setup_sqlite.php`). El `.sqlite` está gitignored
> (se genera); el `.sql` se versiona. Las migraciones `fill_*` siguen existiendo para regenerar la
> base desde la conexión `doctrine`; si las usás, **re-exportá** el dump para mantenerlo al día.

## Arquitectura esencial

- **Dos stacks de PDF** que comparten la constante global `K_PATH_FONTS` con formatos
  distintos → **no** se define globalmente, sino **dentro de cada controlador**:
  - **tc-lib-pdf 8** (API moderna) — `/reporte/test`.
  - **TCPDF 6 + `WrapTcpLib`** (reporteador legacy por YAML) — `/reporte/cursos` y el real
    `ReportMin02` (`/reporte/min_02/{estab?}/{anio?}`).
- **Combinar PDFs**: TCPDI / `PDFMerger` (reporte por área).
- **Dos bases de datos**:
  - **SQLite** (`database/database.sqlite`, conexión por defecto `sqlite`): tablas propias
    de Laravel (users, sessions, cache, jobs) + las tablas de datos de los reportes
    (`680_POF_P`, `658_…`, `664_…`, etc.), pobladas desde `doctrine` vía las migraciones `fill_*`.
    Los modelos `App\Models\*PofP` leen de aquí (su `protected $connection = 'doctrine'` está comentado).
  - **MySQL legacy** (conexión dedicada **`doctrine`**, base `sdo_db`): **origen** de los datos,
    usado **solo por las migraciones `fill_*`** para copiar a `sqlite`. Ya **ningún** modelo ni
    código de app la consulta, así que los reportes corren sin la MySQL accesible. **Solo lectura** —
    NO correr comandos de schema (`migrate`, `db:show`) contra `doctrine` (la MariaDB es vieja).
- Controlador único de reportes: `App\Http\Controllers\ReporteController`. Orquestador del
  reporte real: `App\Libraries\Reports\ReportMin02`.
- **Frontend**: jQuery 3.7 + jQuery UI 1.13 (barras/diálogos de progreso, helper `longOps`).

## Gotchas

- **`app.js` es un módulo diferido**: el JS inline de las vistas debe ir dentro de
  `document.addEventListener('DOMContentLoaded', …)` usando `window.jQuery` (no `$(function(){})`
  directo, porque en el parseo `window.$` aún no existe).
- **Migraciones de relleno `fill_*`** (copian `doctrine` → `sqlite`): el origen trae `NULL`
  en columnas declaradas NOT NULL → `SQLSTATE[23000]: NOT NULL constraint failed`. Según el caso:
  - Booleano con `->default(...)` (`c652_incrementa`, `c652_reduce`): coalescer en el insert con `?? true`.
  - Código/nomenclador sin default lógico (`c650_002_id`, `c664_012_id`): hacer la columna
    `->nullable()` en la migración de creación (inventar un valor falsearía el dato).
  - Recordar: imports `use Illuminate\Support\Facades\DB;` y `use ...\Collection;`;
    `DB::connection('sqlite')->table(...)` (no `::table`); `truncate()` al inicio del `up()`
    para idempotencia (evita `UNIQUE constraint failed` por corridas parciales).
- **Catálogos del reporte**: el área se deriva vía la modalidad (`664.c664_650_id`) porque
  `658.area` viene vacía; el D.E. real es `657.c657_de`.
- **`tc-lib-pdf`** necesita `K_PATH_FONTS` y una fuente importada: `artisan pdf:import-font` (1ª vez).
- **Auth** configurada pero NO scaffoldeada: `welcome.blade.php` referencia rutas `login`/`register`/`/dashboard`
  que aún no existen.

## Endpoints (reportes)

`/reporte/test` (tc-lib-pdf) · `/reporte/cursos` (WrapTcpLib) ·
`/reporte/min_02/{estab?}/{anio?}` (datos reales) · `/reporte/min_02-demo` (barra de progreso) ·
`/reporte/merge-test` (PDFMerger) · `/reporte/longops-demo` · `/reporte/longops-area-demo`
(reporte por área, ruta nombrada `reporte.longops.areaDemo`) — más backends del flujo longOps.
La página de inicio (`/`) tiene un link "Armado de los Reportes por Área" → `longops-area-demo`.
